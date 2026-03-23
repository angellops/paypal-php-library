<?php 
/**
 * Reads and returns the raw SVG content from a given file path.
 * * Useful for embedding SVG code directly into HTML to allow for 
 * CSS manipulation and reducing HTTP requests.
 *
 * @param string $path The absolute or relative server path to the SVG file.
 * @return string Returns the SVG XML content if found, or an empty string if the file does not exist.
 */
function inline_svg($path) {
  if (file_exists($path)) {
    return file_get_contents($path);
  }
  return '';
}