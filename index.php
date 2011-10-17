<?php
// TTML Formatter for Textcube 1.6
// (C) 2004-2009 Needlworks / Tatter Network Foundation

define('FM_PLANTUML_CACHE_DIR', ROOT.'/cache/PlantUML');
require_once(dirname(__FILE__).'/config.php');

function FM_PlantUML_get_cache_dir($subdir) {
	if (!is_dir(FM_PLANTUML_CACHE_DIR)) {
		@mkdir(FM_PLANTUML_CACHE_DIR);
		@chmod(FM_PLANTUML_CACHE_DIR, 0700);
	}
	$path = FM_PLANTUML_CACHE_DIR.DS.$subdir;
	if (!is_dir($path)) {
		@mkdir($path);
		@chmod($path, 0700);
	}
	return $path;
}

function FM_PlantUML_tmpfile($content) {
	$f = tmpfile();
	fwrite($f, $content);
	fseek($f, 0);
	return $f;
}

function FM_PlantUML_readall($f) {
	$view = '';
	while (true) {
		$data = fread($f, 8196);
		if (strlen($data) == 0) {
			break;
		}
		$view .= $data;
	}
	return $view;
}

function FM_PlantUML_convert($content) {
	$tmpfile = FM_PlantUML_tmpfile($content);
	if (is_resource($tmpfile)) {
		$proc = proc_open(
			FM_PLANTUML_JAVA.' -jar '.FM_PLANTUML_JAR.' -p -tsvg -charset UTF-8',
			array(0=>$tmpfile, 1=>array('pipe', 'w')),
			$pipes, null, null);
		if (is_resource($proc)) {
			$view = FM_PlantUML_readall($pipes[1]);
			fclose($pipes[1]);
			proc_close($proc);
		}
		fclose($tmpfile);
	}

	return $view;
}

function FM_PlantUML_convert_to_svg_element($content) {
	$svg = FM_PlantUML_convert($content);
	$start = strpos($svg, '<svg');
	if ($start !== false) {
		$svg = substr($svg, $start);
	}
	return $svg;
}

function FM_PlantUML_transform($target) {
	$output = '';
	// TODO: use a XML parser/transformer
	$start_sig = '<markup type="text/x-plantuml">';
	while (preg_match("@$start_sig@", $target, $start_matches, PREG_OFFSET_CAPTURE)) {
		$start_pos = $start_matches[0][1];
		if (preg_match('@</markup>@', $target, $end_matches, PREG_OFFSET_CAPTURE, $start_pos)) {
			$end_pos = $end_matches[0][1];
			$output .= substr($target, 0, $start_pos);
			$markup = substr($target, $start_pos+strlen($start_sig, $end_pos));
			$svg = FM_PlantUML_convert_to_svg_element($markup);
			$output .= $svg;
			$target = substr($target, $end_pos+strlen('</markup>'));
		}
	}
	$output .= $target;
	return $output;
}

function FM_PlantUML_format($blogid, $id, $content, $keywords = array(), $useAbsolutePath = true, $bRssMode = false) {
	global $service;
	$path = ROOT . "/attach/$blogid";
	$url = "{$service['path']}/attach/$blogid";
	$view = FM_PlantUML_transform($content);
	return $view;
}

function FM_PlantUML_summary($blogid, $id, $content, $keywords = array(), $useAbsolutePath = true) {
	global $blog;
	$view = FM_PlantUML_format($blogid, $id, $content, $keywords, $useAbsolutePath, true);
	if (!$blog['publishWholeOnRSS']) $view = UTF8::lessen(removeAllTags(stripHTML($view)), 255);
	return $view;
}

?>
