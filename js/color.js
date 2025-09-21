function luminance($hexColor) {
    // Convertir la couleur HEX en RGB
    $hexColor = str_replace('#', '', $hexColor);
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));

    // Calculer la luminance relative
    return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
}
