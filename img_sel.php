<?php
$plugin = array(
    'version' => '1.0b2',
    'author' => 'Jon-Michael Deldin',
    'author_uri' => 'http://jmdeldin.com',
    'description' => 'Thickbox-style image selector.',
    'type' => 1,
);

# --- BEGIN PLUGIN CODE ---

if (txpinterface === 'admin')
{
    global $event, $jmdImgSel, $prefs;
    $jmdImgSel_privs = '1,2,3,4,5';
    add_privs('jmd_img_selector', $jmdImgSel_privs);
    register_tab('extensions', 'jmd_img_selector', 'jmd_img_selector');
    register_callback('jmd_img_selector', 'jmd_img_selector');
    add_privs('jmd_img_selector_js', $jmdImgSel_privs);
    register_callback('jmd_img_selector_js', 'jmd_img_selector_js');
    add_privs('jmd_img_selector_thickbox', $jmdImgSel_privs);
    register_callback('jmd_img_selector_thickbox', 'jmd_img_selector_thickbox');
    $jmdImgSel_view = gps('view');
    if ($event === 'article' && $jmdImgSel_view !== 'preview' && $jmdImgSel_view !== 'html')
    {
        ob_start('jmd_img_selector_head');
    }

    $jmdImgSel = new JMD_ImgSelector;
    if (empty($prefs[$jmdImgSel->prefix('tbWidth')]))
    {
        $jmdImgSel->upsertPref('tbWidth', 600, 1);
    }
    if (empty($prefs[$jmdImgSel->prefix('tbHeight')]))
    {
        $jmdImgSel->upsertPref('tbHeight', 600, 1);
    }
    if (empty($prefs[$jmdImgSel->prefix('imgWidth')]))
    {
        $jmdImgSel->upsertPref('imgWidth', 80, 1);
    }
    if (empty($prefs[$jmdImgSel->prefix('imgHeight')]))
    {
        $jmdImgSel->upsertPref('imgHeight', 80, 1);
    }
}

/**
 * jmd_img_selector preferences
 *
 * @param string $event
 * @param string $step
 */
function jmd_img_selector($event, $step)
{
    global $jmdImgSel, $path_to_site, $prefs;
    $out = '<div id="jmd_img_selector" style="width: 500px; margin: 0 auto">';

    if ($step === 'update')
    {
        $settings = array(
            'tbWidth' => gps('tbWidth'),
            'tbHeight' => gps('tbHeight'),
            'imgWidth' => gps('imgWidth'),
            'imgHeight' => gps('imgHeight'),
        );
        foreach ($settings as $key => $value)
        {
            $jmdImgSel->upsertPref($key, $value);
        }
        $msg = $jmdImgSel->gTxt('prefs_updated');
    }

    if ($step === 'css')
    {
        $css = <<<CSS
//inc <imgSel.css>
CSS;
        safe_insert("txp_css", "name='jmd_img_selector', css='" . base64_encode($css) . "'");
        $msg = $jmdImgSel->gTxt('css_created');
    }

    pageTop($jmdImgSel->gTxt('prefs'), (isset($msg) ? $msg : ''));

    // Preferences
    $out .= form(
        fieldset(
            fieldset(
                $jmdImgSel->input('pref_width', 'tbWidth') .
                $jmdImgSel->input('pref_height', 'tbHeight'),
                $jmdImgSel->gTxt('tb_legend')
            ) .
            fieldset(
                $jmdImgSel->input('pref_width', 'imgWidth') .
                $jmdImgSel->input('pref_height', 'imgHeight'),
                $jmdImgSel->gTxt('img_legend')
            ) .
            fInput('submit', 'update', $jmdImgSel->gTxt('update')) .
            eInput('jmd_img_selector') .
            sInput('update')
            , $jmdImgSel->gTxt('prefs_legend')
        )
    );

    // Check if CSS file exists
    $rs = safe_field('name', 'txp_css', 'name="jmd_img_selector"');
    if (empty($rs))
    {
        $out .= form(
            fieldset(
                fInput('submit', 'submit', $jmdImgSel->gTxt('create_css')) .
                eInput('jmd_img_selector') .
                sInput('css'),
                $jmdImgSel->gTxt('css_legend')
            )
        );
    }
    echo $out;
}

/**
 * Injects Thickbox CSS and JS links. Also includes prefs-pane CSS.
 *
 * @param string $buffer
 */
function jmd_img_selector_head($buffer)
{
    $find = '</head>';
    $head = <<<EOD
<link href="./css.php?n=jmd_img_selector" rel="stylesheet" type="text/css"/>
<!--[if !IE]><!-->
<style type="text/css">
/*Safari - circumvent the cutoff images-bug*/
#jmdImgSel_images li
{
    position: relative;
}
#jmdImgSel_images img
{
    position: absolute;
    top: 0;
}
</style>
<!--<![endif]-->
<script src="./?event=jmd_img_selector_js" type="text/javascript"></script>
EOD;
    return str_replace($find, $head . $find, $buffer);
}

/**
 * Thickbox JS
 *
 * @param string $event
 * @param string $step
 */
function jmd_img_selector_js($event, $step)
{
    global $jmdImgSel, $prefs;
    header('content-type: text/javascript; charset=utf-8');

    echo <<<EOD
var jmdImgSel = {
    config: {
        'addImgId': 'jmdImgSel_add',
        'articleImgField': 'article-image',
        'bodyField': 'body',
        'controlsId': 'jmdImgSel_controls',
        'closeId': 'jmdImgSel_close',
        'closeText': '{$jmdImgSel->gTxt('close_window')}',
        'contentId': 'jmdImgSel_content',
        'linkName': '{$jmdImgSel->gTxt('link_name')}',
        'imgNameId': 'jmdImgSel_imgName',
        'infoId': 'jmdImgSel_info',
        'overlayId': 'jmdImgSel_overlay',
        'modalId': 'jmdImgSel_modal',
        'selectId': 'jmdImgSel_categories',
        'typeId': 'jmdImgSel_type',
        'ulId': 'jmdImgSel_images',
        'updateId': 'jmdImgSel_msg',
        'updateMsg': '{$jmdImgSel->gTxt('update_msg')}',
        'uri': './?event=jmd_img_selector_thickbox',
        'windowHeight': {$prefs['jmd_img_selector_tbHeight']},
        'windowWidth': {$prefs['jmd_img_selector_tbWidth']}
    }
};

//inc <imgSel.js>
EOD;
    exit;
}

/**
 * Thickbox HTML
 *
 * @param string $event
 * @param string $step
 */
function jmd_img_selector_thickbox($event, $step)
{
    global $img_dir, $jmdImgSel, $prefs;
    $lang = LANG;
    $dir = gTxt('lang_dir');
    echo <<<HTML
<a href="#jmdImgSel" id="jmdImgSel_close">
    X
</a>
<div id="jmdImgSel_controls">
    <div id="jmdImgSel_options">
        <label>{$jmdImgSel->gTxt('browse')}
            <select id="jmdImgSel_categories">
                <option value="root">root</option>
                {$jmdImgSel->displayCategories()}
            </select>
        </label>
        <label>{$jmdImgSel->gTxt('insert_as')}
            <select id="jmdImgSel_type">
                <option value="article_image">
                    {$jmdImgSel->gTxt('article_image')}
                </option>
                <option value="body">
                    {$jmdImgSel->gTxt('body')}
                </option>
                <option value="thumbnail">
                    {$jmdImgSel->gTxt('thumbnail')}
                </option>
                <option value="popup">
                    {$jmdImgSel->gTxt('popup')}
                </option>
            </select>
        </label>
    </div>
    <button id="jmdImgSel_add" type="button">
        {$jmdImgSel->gTxt('add_img')}
    </button>
    <div id="jmdImgSel_info">
        <span id="jmdImgSel_imgName">
            &nbsp;
        </span>
    </div>
</div>

<ul id="jmdImgSel_images">
    {$jmdImgSel->displayImages()}
</ul>
HTML;
exit;
}


class JMD_ImgSelector
{
    private $categories, $height, $images, $width;

    public function __construct()
    {
        global $prefs;
        if (gps('event') === 'jmd_img_selector_thickbox')
        {
            $this->categories = array();
            $this->height = $prefs[$this->prefix('imgHeight')];
            $this->width = $prefs[$this->prefix('imgWidth')];
            $this->getImages();
        }
    }

    /**
     * Returns a list of categories
     */
    public function displayCategories()
    {
        $out = '';
        sort($this->categories);
        foreach ($this->categories as $cat)
        {
            $out .= '<option value="' . $cat .'">' . $cat . '</option>';
        }

        return $out;
    }

    public function displayImages()
    {
        global $img_dir;
        $out = '';
        foreach ($this->images as $img)
        {
            extract($img);
            $name = htmlspecialchars($name);
            $uri = hu . $img_dir . DS . $id;
            if ($thumbnail == 1)
            {
                $path = dirname(txpath) . DS . $img_dir . DS . $id . 't' . $ext;
                // in case the thumbnail was deleted from the filesystem
                if (file_exists($path))
                {
                    $uri .= 't';
                    list($w, $h, $type, $attr) = getimagesize($path);
                }
            }
            $uri .= $ext;
            // Landscape
            if (($w > $h) && ($w >= $this->width) && ($h > $this->height))
            {
                $style = "height: {$this->height}px";
            }
            // Portrait
            elseif (($w < $h) && ($h >= $this->height) && ($h > $this->width))
            {
                $style = "width: {$this->width}px";
            }
            // Square or small images
            else
            {
                $style = "height: {$this->height}px; width: {$this->width}px";
            }
            $out .= <<<IMG
<li class="{$category}"
    id="img{$id}"
    style="height: {$this->height}px; width: {$this->width}px;"
    title="{$name}">
    <img alt="{$name}" src="{$uri}" style="{$style}"/>
</li>
IMG;
        }

        return $out;
    }

    /**
     * fInput() shortcut
     *
     * @param string $label Key for $textarray
     * @param string $name Input name
     * @param mixed $value Input value
     * @param int $size Input size
     */
    public function input($label, $name, $size=3)
    {
        global $prefs;
        return '<label> ' . $this->gTxt($label) .
            fInput('text', $name, $prefs[$this->prefix($name)], '', '', '', $size) . '</label><br/>';
    }

    /**
     * Pushes content to $this->categories and $this->images
     */
    private function getImages()
    {
        $this->images = getRows("select id, name, category, ext, w, h, thumbnail
            from " . safe_pfx('txp_image'));
        if ($this->images)
        {
            foreach ($this->images as $img)
            {
                if (!in_array($img['category'], $this->categories)
                    && $img['category'] !== '')
                {
                    array_push($this->categories, $img['category']);
                }
            }
        }
    }

    /**
     * Localizable strings
     *
     * @param string $key
     */
    public function gTxt($key)
    {
        $i10n = array(
            'add_img' => 'Add image',
            'article_image' => 'Article image',
            'body' => 'Body',
            'browse' => 'Browse category:',
            'create_css' => 'Create CSS',
            'close_window' => 'Close',
            'css_created' => 'CSS created.',
            'css_legend' => 'Create jmd_img_selector CSS',
            'img_legend' => 'Image settings',
            'insert_as' => 'Insert as:',
            'link_name' => 'Insert Image',
            'no_images' => 'No images were found.',
            'no_tb_css' => 'thickbox.css was not found.',
            'no_tb_js' => 'thickbox.js was not found.',
            'page_title' => 'Image selector',
            'popup' => 'Popup',
            'pref_width' => 'Width',
            'pref_height' => 'Height',
            'prefs_legend' => 'jmd_img_selector preferences',
            'prefs_updated' => 'Preferences updated.',
            'tb_dir' => 'Path to Thickbox directory',
            'tb_legend' => 'Thickbox settings',
            'thumbnail' => 'Thumbnail',
            'update' => 'Update',
            'update_msg' => 'Images added.',
        );

        return strtr($key, $i10n);
    }

    /**
     * Shortcut for 'jmd_img_selector'
     *
     * @param string $suffix Text to append to 'jmd_img_selector'
     * @return string
     */
    public function prefix($suffix='')
    {
        $out = 'jmd_img_selector';
        if ($suffix)
        {
            $out .= '_' . $suffix;
        }

        return $out;
    }

    /**
     * Insert or update a preference.
     *
     * @param string $name Non-prefixed preference name
     * @param mixed $value
     * @param bool $insert
     */
    public function upsertPref($name, $value, $insert=0)
    {
        global $prefs;
        $name = $this->prefix($name);
        $prefs[$name] = $value;
        if ($insert === 1)
        {
            safe_insert("txp_prefs", "prefs_id=1,
                name='$name',
                val='$value',
                type=2,
                event='admin',
                html='text_input',
                position=0
            ");
        }
        else
        {
            safe_update("txp_prefs", "val='$value'", "name='$name'");
        }
    }
}

# --- END PLUGIN CODE ---

?>

