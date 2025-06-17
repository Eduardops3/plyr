<?php
/**
 * Plugin Name:     QR Shapes Generator
 * Description:     Genera un QR en SVG con chillerlan/php-qrcode y permite cambiar la forma de los módulos.
 * Version:         1.0
 * Author:          Tú
 */

if (!defined('ABSPATH')) {
    exit;
}

$autoload = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
if (!file_exists($autoload)) {
    add_action('admin_notices', function(){
        echo '<div class="notice notice-error"><p><strong>QR Shapes Generator:</strong> falta <code>vendor/autoload.php</code>. Ejecuta <code>composer install</code> allí.</p></div>';
    });
    return;
}
require_once $autoload;

add_shortcode('qr_shapes','qrshapes_shortcode');
function qrshapes_shortcode(){
    wp_enqueue_script(
        'qr-shapes',
        plugin_dir_url(__FILE__).'script.js',
        [],
        '1.0',
        true
    );
    wp_localize_script('qr-shapes','qrshapes',[
      'ajax_url' => admin_url('admin-ajax.php')
    ]);
    ob_start();
    ?>
    <div id="qr-controls" style="margin-bottom:1em;">
      <label>
        Texto/URL:
        <input type="text" id="qr-text" value="https://unikris.com" style="padding:4px;width:200px">
      </label>
      <label style="margin-left:1em;">
        Forma:
        <select id="shape-select" style="padding:4px">
          <option value="square">Cuadrados</option>
          <option value="circle">Círculos</option>
          <option value="dot">Puntos</option>
          <option value="diamond">Diamantes</option>
        </select>
      </label>
      <button id="generate" style="margin-left:1em;padding:6px 12px">Generar QR</button>
    </div>
    <div id="qr-container" style="min-height:200px;border:1px solid #ccc;padding:1em"></div>
    <?php
    return ob_get_clean();
}

function qrshapes_generate_ajax(){
    @ini_set('display_errors', 0);
    error_reporting(0);
    while(ob_get_level()){ ob_end_clean(); }

    nocache_headers();
    header('Content-Type: image/svg+xml; charset=utf-8');

    try {
        if (!class_exists('\\chillerlan\\QRCode\\QRCode')){
            throw new \Exception('Falta chillerlan/php-qrcode.');
        }
        $data = isset($_POST['data']) ? sanitize_text_field($_POST['data']) : '';
        $options = new \\chillerlan\\QRCode\\QROptions([
            'version'=>5,
            'outputType'=>\\chillerlan\\QRCode\\QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel'=>\\chillerlan\\QRCode\\QRCode::ECC_L,
            'scale'=>6,
            'imageBase64'=>false,
            'drawLightModules'=>true,
            'markupDark'=>'#000',
            'markupLight'=>'#fff',
            'drawCircularModules'=>false,
            'keepAsSquare'=>[
                \\chillerlan\\QRCode\\QRMatrix::M_FINDER_PATTERN,
                \\chillerlan\\QRCode\\QRMatrix::M_FINDER_DOT,
                \\chillerlan\\QRCode\\QRMatrix::M_ALIGNMENT_PATTERN,
            ],
        ]);
        $svg = (new \\chillerlan\\QRCode\\QRCode($options))->render($data);
        $svg = preg_replace('/\A[\x00-\x1F\x7F]+/u', '', $svg);
        echo $svg;
    }
    catch(\Throwable $e){
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Error al generar SVG: '.$e->getMessage();
    }
    exit;
}
add_action('wp_ajax_qrshapes_generate','qrshapes_generate_ajax');
add_action('wp_ajax_nopriv_qrshapes_generate','qrshapes_generate_ajax');
