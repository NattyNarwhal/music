<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Moahmed-Ismail MEJRI <imejri@hotmail.com>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Moahmed-Ismail MEJRI 2022
 * @copyright Pauli Järvinen 2022
 */

namespace OCA\Music\Utility;

use OCA\Music\AppFramework\Core\Logger;

/**
 * MetaData radio utility functions
 */
class RadioMetadata {

	private $logger;

	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	/**
	 * Loop through the array and try to find the given key. On match,
	 * return the text in the array cell following the key.
	 */
	private static function findStrFollowing(array $data, string $key) : ?string {
		foreach ($data as $value) {
			$find = \strstr($value, $key);
			if ($find !== false) {
				return \substr($find, \strlen($key));
			}
		}
		return null;
	}

	private static function parseStreamUrl(string $url) : array {
		$ret = [];
		$parse_url = \parse_url($url);

		$ret['port'] = 80;
		if (isset($parse_url['port'])) {
			$ret['port'] = $parse_url['port'];
		} else if ($parse_url['scheme'] == "https") {
			$ret['port'] = 443;
		}

		$ret['scheme'] = $parse_url['scheme'];
		$ret['hostname'] = $parse_url['host'];
		$ret['pathname'] = $parse_url['path'];

		if (isset($parse_url['query'])) {
			$ret['pathname'] .= "?" . $parse_url['query'];
		}

		if ($parse_url['scheme'] == "https") {
			$ret['sockAddress'] = "ssl://" . $ret['hostname'];
		} else {
			$ret['sockAddress'] = $ret['hostname'];
		}

		return $ret;
	}

	private static function parseTitleFromStreamMetadata($fp) : ?string {
		$meta_length = \ord(\fread($fp, 1)) * 16;
		if ($meta_length) {
			$metadatas = \explode(';', \fread($fp, $meta_length));
			$title = self::findStrFollowing($metadatas, "StreamTitle=");
			if ($title) {
				return Util::truncate(\trim($title, "'"), 256);
			}
		}
		return null;
	}

	private function readMetadata(string $metaUrl, callable $parseResult) : ?string {
		list('content' => $content, 'status_code' => $status_code, 'message' => $message) = HttpUtil::loadFromUrl($metaUrl);

		if ($status_code == 200) {
			return $parseResult($content);
		} else {
			$this->logger->log("Failed to read $metaUrl: $status_code $message", 'debug');
			return null;
		}
	}

	public function readShoutcastV1Metadata(string $streamUrl) : ?string {
		// cut the URL from the last '/' and append 7.html
		$lastSlash = \strrpos($streamUrl, '/');
		$metaUrl = \substr($streamUrl, 0, $lastSlash) . '/7.html';

		return $this->readMetadata($metaUrl, function ($content) {
			$content = \strip_tags($content); // get rid of the <html><body>...</html></body> decorations
			$data = \explode(',', $content);
			return \count($data) > 6 ? \trim($data[6]) : null; // the title field is optional
		});
	}

	public function readShoutcastV2Metadata(string $streamUrl) : ?string {
		// cut the URL from the last '/' and append 'stats'
		$lastSlash = \strrpos($streamUrl, '/');
		$metaUrl = \substr($streamUrl, 0, $lastSlash) . '/stats';

		return $this->readMetadata($metaUrl, function ($content) {
			$rootNode = \simplexml_load_string($content, \SimpleXMLElement::class, LIBXML_NOCDATA);
			return (string)$rootNode->SONGTITLE;
		});
	}

	public function readIcacastMetadata(string $streamUrl) : ?string {
		// cut the URL from the last '/' and append 'status-json.xsl'
		$lastSlash = \strrpos($streamUrl, '/');
		$metaUrl = \substr($streamUrl, 0, $lastSlash) . '/status-json.xsl';

		return $this->readMetadata($metaUrl, function ($content) {
			$parsed = \json_decode($content, true);
			return $parsed['icecasts']['source']['title']
				?? $parsed['icecasts']['source']['yp_currently_playing']
				?? null;
		});
	}

	public function readIcyMetadata(string $streamUrl, int $maxattempts, int $maxredirect) : ?string {
		$timeout = 10;
		$streamTitle = null;
		$pUrl = self::parseStreamUrl($streamUrl);
		if ($pUrl['sockAddress'] && $pUrl['port']) {
			$fp = \fsockopen($pUrl['sockAddress'], $pUrl['port'], $errno, $errstr, $timeout);
			if ($fp !== false) {
				$out = "GET " . $pUrl['pathname'] . " HTTP/1.1\r\n";
				$out .= "Host: ". $pUrl['hostname'] . "\r\n";
				$out .= "Accept: */*\r\n";
				$out .= HttpUtil::userAgentHeader() . "\r\n";
				$out .= "Icy-MetaData: 1\r\n";
				$out .= "Connection: Close\r\n\r\n";
				\fwrite($fp, $out);
				\stream_set_timeout($fp, $timeout);

				$header = \fread($fp, 1024);
				$headers = \explode("\n", $header);

				if (\strpos($headers[0], "200 OK") !== false) {
					$interval = self::findStrFollowing($headers, "icy-metaint:") ?? '0';
					$interval = (int)\trim($interval);

					if ($interval > 0 && $interval <= 64*1024) {
						$attempts = 0;
						while ($attempts < $maxattempts && $streamTitle === null) {
							$bytesToSkip = $interval;
							if ($attempts === 0) {
								// The first chunk containing the header may also already contain the beginning of the body,
								// but this depends on the case. Subtract the body bytes which we already got.
								$headerEndPos = \strpos($header, "\r\n\r\n") + 4;
								$bytesToSkip -= \strlen($header) - $headerEndPos;
							}

							\fseek($fp, $bytesToSkip, SEEK_CUR);

							$streamTitle = self::parseTitleFromStreamMetadata($fp);

							$attempts++;
						}
					}
				} else if ($maxredirect > 0 && \strpos($headers[0], "302 Found") !== false) {
					$location = self::findStrFollowing($headers, "Location: ");
					if ($location) {
						$location = \trim($location, "\r");
						$streamTitle = $this->readIcyMetadata($location, $maxattempts, $maxredirect-1);
					}
				}
				\fclose($fp);
			}
		}

		return $streamTitle === '' ? null : $streamTitle;
	}

}