<?php

	//lib_nikolay_webcam_api.php v 2022-09-01-17-20 https://t.me/skl256
	
	/*Перед использованием необходимо убедиться в наличии ffmpeg, php-mbstring при необходимости установить: sudo apt-get install ffmpeg php-mbstring
	
	Использование:
	
	require_once("lib_nikolay_webcam_api.php"); //Включить в скрипт библиотеку с nikolay_telegram_api
	getFromIpWebcam($camera_config, $video_duration = 0) где $camera_config - конфигурация, пример ниже
	
	lib_nikolay_webcam_api.php записывает логи с помощью функции writeLog($level, $string), и определяет доступность интерфейса с помощью pingInterface($ip);
	для корректной работы необходимо определить данную функцию самостоятельно или воспользоваться примером, расположенном в конце настоящего файла

	Пример конфигурации:
	define("CAMERA0_CONFIG", array( //Входные параметры при получении от внешнего окружения должны быть проверены на escapeshellarg
		'NAME' => "", //DEFAULT = same as 'IP'
		'FFMPEG_BIN' => "", //DEFAULT = "ffmpeg"
		'PROTO' => "", //DEFAULT = "http", supports "http", "https", "rtsp", "rtp"
		'IP' => "", //DEFAULT = "localhost", EXAMPLE = "192.168.0.123"
		'PORT' => "", //DEFAULT = ""
		'PATH' => "", //DEFAULT = "/video"
		'HTTP_BASIC_AUTH' => "", //DEFAULT = "", EXAMPLE = "login:password"
		'VIDEO_I_FRAMERATE' => "", //DEFAULT = "", EXAMPLE = 5, try to use if you have problems with video speed
		'ADD_STRING_TO_END_V' => "", //DEFAULT = "", EXAMPLE = "-an" ONLY FOR VIDEO
		'ADD_STRING_TO_END_P' => "", //DEFAULT = "", EXAMPLE = "-f mjpeg" for Rubitek cam, ONLY FOR PHOTO
		'TIMEOUT' => "", //DEFAULT = 10, without taking into account the duration of the video
		'IS_ANDRIOID_IPWEBCAM_APP' => "", //DEFAULT = false
		'ANDROID_IPWEBCAM_AUDIO_ENABLED' => "", //DEFAULT = false
		'DIRECTORY' => "", //DEFAULT = "camera"
		'DEBUG_MODE' => "" //DEFAULT = false, return $string of file_get_contents($string) or exec($string) instead of get a file
	));*/
	
	function getFromIpWebcam($camera_config, $video_duration = 0) { //Возвращает путь к фотографии с камеры или false в случае ошибки
		$start_time = time(); //В данный момент $start_time не используется ни для каких метрик
		writeLog("PROCESS", "CALL FUNCTION getFromIpWebcam(" . json_encode($camera_config) . ", $video_duration)");
		
		$camera_name = $camera_config['IP'];
		if ((isset($camera_config['NAME'])) && ($camera_config['NAME'] != "")) {
			$camera_name = $camera_config['NAME'];
		}
		
		$timeout = 10;
		if ((isset($camera_config['TIMEOUT'])) && ($camera_config['TIMEOUT'] != "")) {
			$timeout = $camera_config['TIMEOUT'];
		}
		$timeout = $timeout + $video_duration;
		
		$ffmpeg_string = "ffmpeg ";
		if ((isset($camera_config['FFMPEG_BIN'])) && ($camera_config['FFMPEG_BIN'] != "")) {
			$ffmpeg_string =  $camera_config['FFMPEG_BIN'] . " ";
		}
		$ffmpeg_string = "timeout $timeout $ffmpeg_string";
		
		$dir_name = "camera";
		if ((isset($camera_config['DIRECTORY'])) && ($camera_config['DIRECTORY'] != "")) {
			$dir_name = $camera_config['DIRECTORY'];
		}
		if (!is_dir($dir_name)) {
			mkdir($dir_name, 0777, true);
		}
		
		$url = "";
		if ((isset($camera_config['PROTO'])) && ($camera_config['PROTO'] != "")) {
			$url = $url . $camera_config['PROTO'] . "://";
		} else {
			$url = $url . "http://";
		}
		if ((isset($camera_config['HTTP_BASIC_AUTH'])) && ($camera_config['HTTP_BASIC_AUTH'] != "")) {
			$url = $url . $camera_config['HTTP_BASIC_AUTH'] . "@";
		}
		if ((isset($camera_config['IP'])) && ($camera_config['IP'] != "")) {
			$url = $url . $camera_config['IP'];
		} else {
			$url = $url . "localhost";
		}
		if ((isset($camera_config['PORT'])) && ($camera_config['PORT'] != "")) {
			$url = $url . ":" . $camera_config['PORT'];
		}
		
		$add_string_to_end_v = "";
		if ((isset($camera_config['ADD_STRING_TO_END_V'])) && ($camera_config['ADD_STRING_TO_END_V'] != "")) {
			$add_string_to_end_v = " " . $camera_config['ADD_STRING_TO_END_V'] . " ";
		}
		
		$add_string_to_end_p = "";
		if ((isset($camera_config['ADD_STRING_TO_END_P'])) && ($camera_config['ADD_STRING_TO_END_P'] != "")) {
			$add_string_to_end_p = " " . $camera_config['ADD_STRING_TO_END_P'] . " ";
		}
		
		$path = "/video";
		if ((isset($camera_config['PATH'])) && ($camera_config['PATH'] != "")) {
			$path = $camera_config['PATH'];
		}
		
		$i_framerate = "";
		if ((isset($camera_config['VIDEO_I_FRAMERATE'])) && ($camera_config['VIDEO_I_FRAMERATE'] != "")) {
			$i_framerate = "-r " . $camera_config['VIDEO_I_FRAMERATE'];
		}
		
		if ((!isset($camera_config['IS_ANDRIOID_IPWEBCAM_APP'])) || ($camera_config['IS_ANDRIOID_IPWEBCAM_APP'] == "")) {
			$camera_config['IS_ANDRIOID_IPWEBCAM_APP'] = false;
		}
		
		if ((!isset($camera_config['ANDROID_IPWEBCAM_AUDIO_ENABLED'])) || ($camera_config['ANDROID_IPWEBCAM_AUDIO_ENABLED'] == "")) {
			$camera_config['ANDROID_IPWEBCAM_AUDIO_ENABLED'] = false;
		}
		
		if ((!isset($camera_config['DEBUG_MODE'])) || ($camera_config['DEBUG_MODE'] == "")) {
			$camera_config['DEBUG_MODE'] = false;
		}
		
		$rand_string = mb_substr(md5(rand() . time()), 0, 16);

		if ((pingInterface($camera_config['IP'])) || ($camera_config['DEBUG_MODE'])) {
			if (($video_duration == 0) && ($camera_config['IS_ANDRIOID_IPWEBCAM_APP'])) {
				$filename = "$dir_name/image_$camera_name" . "_" . date("Ymd_His", time()) . "_$rand_string.jpg";
				$default_socket_timeout = ini_get("default_socket_timeout");
				ini_set("default_socket_timeout", $timeout);
				writeLog("PROCESS", "BEGIN COPY PHOTO IN getFromIpWebcam() with file_put_contents($filename, file_get_contents($url/shot.jpg))");
				if ($camera_config['DEBUG_MODE']) {
					return "$url/shot.jpg";
				}
				$put_contents_size = file_put_contents($filename, file_get_contents("$url/shot.jpg"));
				writeLog("PROCESS", "END COPY PHOTO IN getFromIpWebcam(), PUT_CONTENTS_SIZE = " . $put_contents_size);
				ini_set("default_socket_timeout", $default_socket_timeout);
				if ((file_exists($filename)) && (filesize($filename) > 0)) {
					return $filename;
				} else {
					writeLog("ERROR", "FILE NOT CREATED IN getFromIpWebcam() OR FILESIZE = 0");
					return false;
				}
			} else {
				$filename = "";
				if ($video_duration == 0) {
					$filename = "$dir_name/image_$camera_name" . "_" . date("Ymd_His", time()) . "_$rand_string.jpg";
					$ffmpeg_string = $ffmpeg_string . "-i \"$url$path\" -y -vframes 1 $add_string_to_end_p \"$filename\"";
				} else if ($video_duration > 0) {
					$filename = "$dir_name/video_$camera_name" . "_" . date("Ymd_His", time()) . "_$rand_string.mp4";
					$escapeshellarg_video_duration = escapeshellarg($video_duration);
					$ffmpeg_string_audio_aipwc = ($camera_config['ANDROID_IPWEBCAM_AUDIO_ENABLED']) ? ("-i \"$url/audio.opus\" -c:a mp3") : ("");
					$ffmpeg_string = $ffmpeg_string . "$i_framerate -i \"$url$path\" $ffmpeg_string_audio_aipwc -t $escapeshellarg_video_duration -y $add_string_to_end_v \"$filename\"";
				}
				writeLog("PROCESS", "BEGIN getFromIpWebcam() with exec($ffmpeg_string)");
				$exec_output = array();
				$exec_exit_code = 99;
				if ($camera_config['DEBUG_MODE']) {
					return $ffmpeg_string;
				}
				exec($ffmpeg_string, $exec_output, $exec_exit_code);
				writeLog("PROCESS", "END getFromIpWebcam() with exec($ffmpeg_string) CODE = $exec_exit_code");
				if ((file_exists($filename)) && ($exec_exit_code != 124)) {
					writeLog("PROCESS", "FILESIZE = " . filesize($filename) . " FILE CREATED IN getFromIpWebcam() with exec($ffmpeg_string)");
					return $filename;
				} else {
					writeLog("ERROR", "FILE NOT CREATED IN getFromIpWebcam() with exec($ffmpeg_string) CODE = $exec_exit_code");
					return false;
				}
			}
		} else {
			return false;
		}
	}
	
	/*Пример настройки логгирования и определения функции writeLog()
	
	define("LOG_PATH_SALT", "01234567890abcdef"); //Указать секректную строку, которая будет добавляться к имени .txt файла лога, для затруднения несанционированного доступа
		//к файлу через HTTP
	ini_set("log_errors", "On");
	ini_set("error_log", "log_PHP_" . LOG_PATH_SALT . ".txt");
	
	function writeLog($level, $string) {
		$line = date("Y-m-d H:i:s", time()) . " " . $level . ": " . htmlspecialchars($string, ENT_NOQUOTES) . "\n";
		file_put_contents("log_MAIN_" . LOG_PATH_SALT . ".txt", $line, FILE_APPEND);
		file_put_contents("log_" . $level . "_" . LOG_PATH_SALT . ".txt", $line, FILE_APPEND);
	}
	
	Пример функции проверки связи с интерфейсом
	
	function pingInterface($ip, $is_error_if_not_pinging = true) { //Возвращает true если ping прошёл успешно или false в случае ошибки
		if (!mb_strpos(exec("ping -c 1 $ip | grep \"received\""), "0 received")) {
			return true;
		} else {
			if ($is_error_if_not_pinging) {
				writeLog("ERROR", "UNREACHABLE INTERFACE $ip");
			}
			return false;
		}
	}
	*/
?>
