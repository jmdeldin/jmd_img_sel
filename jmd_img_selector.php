<?php
$plugin = array(
    'version' => '1.0b1',
    'author' => 'Jon-Michael Deldin',
    'author_uri' => 'http://jmdeldin.com',
    'description' => 'Thickbox-style image selector.',
    'type' => 1,
);
if (!defined('txpinterface')) include_once '../zem_tpl.php';

if (0) {
?>

# --- BEGIN PLUGIN HELP ---

h1. jmd_img_selector: Thickbox-style image selector

"Forum thread":http://forum.textpattern.com/viewtopic.php?id=27456, "hg repo":http://www.bitbucket.org/jmdeldin/jmd_img_selector/overview/

*Requires:* PHP 5, TXP 4.0.6+

h2. Setup

After installing and activating the plugin, you need to "create a CSS file":?event=jmd_img_selector.

h3. Updating

* Delete jmd_img_selector stylesheet from Presentation>Style
* Create a new CSS file
* Clear your browser cache

h2. Browser support

* Firefox 3
* Internet Explorer 7
* Opera 9.5
* Safari 3

# --- END PLUGIN HELP ---

<?php
}

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
#jmdImgSel_overlay
{
    background: #000;
    height: 100%;
    opacity: 0.8;
    /*ie*/
    filter: alpha(opacity=80);
    position: fixed;
    top: 0;
    width: 100%;
}

#jmdImgSel_modal, #jmdImgSel_modal *
{
    margin: 0;
    padding: 0;
}

#jmdImgSel_modal
{
    background: #fff;
    position: absolute;
    top: 35px;
}

#jmdImgSel_close
{
    background: #000;
    border: 3px solid #fff;
    -moz-border-radius: 1.1em;
    -webkit-border-radius: 1.1em;
    -webkit-box-shadow: rgba(0, 0, 0, 0.3) 2px 3px 3px;
    color: #fff;
    font-size: 12px;
    font-weight: 900;
    left: -20px;
    padding: 0.25em 0.55em;
    position: absolute;
    text-decoration: none;
    top: -20px;
}

#jmdImgSel_controls
{
    background: #eee;
    margin: 0 0 8px;
    padding: 5px 10px 5px;
    /*ie*/
    zoom: 1;
}
    #jmdImgSel_controls:after
    {
        clear: both;
        content: '.';
        display: block;
        height: 0;
        visibility: hidden;
    }
    #jmdImgSel_controls button
    {
        float: right;
        font-size: 100%;
        padding: 0 0.5em;
    }

#jmdImgSel_options
{
    float: left;
    width: 85%;
}
    /*lazy way of targeting the two labels.*/
    #jmdImgSel_options label
    {
        float: right;
    }
    #jmdImgSel_options label:first-child
    {
        float: left;
    }

#jmdImgSel_info
{
    clear: both;
    color: #333;
    font-weight: 900;
}

#jmdImgSel_msg
{
    padding: 0 10px;
}

#jmdImgSel_imgName
{

    font-weight: 900;
}

#jmdImgSel_images
{
    clear: both;
    overflow: auto;
    padding: 0 0 0 10px;
}
    #jmdImgSel_images li
    {
        border: 5px solid #ccc;
        float: left;
        margin: 0 5px 10px 0;
        opacity: 0.6;
        overflow: hidden;
    }
        #jmdImgSel_images li:hover
        {
            border-color: #999;
            opacity: 1;
        }
        #jmdImgSel_images li.selected
        {
            border-color: #666;
            opacity: 1;
        }
    #jmdImgSel_images img
    {
        line-height: 0;
    }
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

// Stores selected images' IDs
jmdImgSel.selected = [];

/**
 * Cross-browser addEventListener
 *
 * @param object obj
 * @param string event
 * @param function func
 */
jmdImgSel.addEvent = function(obj, event, func)
{
    if (obj.attachEvent)
    {
        obj['e' + event + func] = func;
        obj[event + func] = function()
        {
            obj['e' + event + func](window.event);
        };
        obj.attachEvent('on' + event, obj[event + func]);
    }
    else
    {
        obj.addEventListener(event, func, false)
    }
};

/**
 * Checks if a value is in an array.
 *
 * @param array haystack
 * @param int needle
 */
jmdImgSel.inArray = function(haystack, needle)
{
    for (var i = 0; i < haystack.length; i++)
    {
        if (haystack[i] === needle)
        {
            return true;
        }
    }
};

/**
 * Splice an array when the element's index is unknown
 *
 * @param array haystack
 * @param int needle
*/
jmdImgSel.unkSplice = function(haystack, needle)
{
    for (var i = 0; i < haystack.length; i++)
    {
        if (haystack[i] == needle)
        {
            haystack.splice(i, 1);
        }
    }
};

/**
 * Returns the value of an element's CSS property.
 *
 * @param string el
 * @param string prop
 */
jmdImgSel.getStyle = function(el, prop)
{

    var val;
    if (document.defaultView && document.defaultView.getComputedStyle)
    {
        var el = document.defaultView.getComputedStyle(el, '');
        val = el.getPropertyValue(prop);
    }
    else
    {
        // IE
        var match = prop.match(/-\w/);
        if (match)
        {
            prop = prop.replace(match, match[0].toUpperCase().substr(1, 1));
        }
        val = el.currentStyle[prop];
    }

    return val;
};

/**
 * Toggles an element's visibility.
 *
 * @param obj el
 */
jmdImgSel.toggle = function(el)
{
    el.style.display = (el.style.display == '' ? 'none' : '');
};

/**
 * Creates an "Insert image" link in the first column.
 */
jmdImgSel.insertLink = function()
{
    var leftCol = document.getElementById('article-col-1');
    if (leftCol)
    {
        var h3 = document.createElement('h3');
        h3.className = 'plain';
        var link = document.createElement('a');
        link.setAttribute('href', '#jmdImgSel');
        link.onclick = function()
        {
            jmdImgSel.getExisting();
            if (document.getElementById(jmdImgSel.config.overlayId))
            {
                jmdImgSel.toggleModal();
            }
            else
            {
                jmdImgSel.getContents();
            }
        };
        link.appendChild(document.createTextNode(jmdImgSel.config.linkName));
        h3.appendChild(link);
        leftCol.insertBefore(h3, leftCol.firstChild);
    }
};

/**
 * Pushes the article-image field values onto jmdImgSel.selected.
 */
jmdImgSel.getExisting = function()
{
    var field = document.getElementById(jmdImgSel.config.articleImgField);
    if (field)
    {
        var val = field.value;
        if (val)
        {
            var selected = jmdImgSel.selected.toString();
            if (val.match(','))
            {
                val = val.split(',');
                for (var i = 0; i < val.length; i++)
                {
                    if (!jmdImgSel.inArray(jmdImgSel.selected, val[i]))
                    {
                        jmdImgSel.selected.push(val[i])
                    }
                }
            }
            else
            {
                if (!jmdImgSel.inArray(jmdImgSel.selected, val))
                {
                    jmdImgSel.selected.push(val);
                }
            }
        }
    }
};

/**
 * Performs an XMLHttpRequest.
 */
jmdImgSel.getContents = function()
{
    var xhr = false;
    if (window.ActiveXObject)
    {
        xhr = new ActiveXObject('Microsoft.XMLHTTP');
    }
    else
    {
        xhr = new XMLHttpRequest();
    }
    xhr.onreadystatechange = function()
    {
        if (xhr.readyState === 4)
        {
            if (xhr.status === (200 || 304))
            {
                jmdImgSel.createModal(xhr.responseText);
                jmdImgSel.init();
            }
        }
    };
    xhr.open('GET', jmdImgSel.config.uri, true);
    xhr.send(null);
};

/**
 * Creates the image selector modal window.
 *
 * @param string contents
 */
jmdImgSel.createModal = function(contents)
{
    var overlay = document.createElement('div');
    overlay.setAttribute('id', jmdImgSel.config.overlayId);
    overlay.setAttribute('title', jmdImgSel.config.closeText);
    jmdImgSel.addEvent(overlay, 'click', jmdImgSel.toggleModal);
    document.body.appendChild(overlay);

    var modal = document.createElement('div');
    modal.setAttribute('id', jmdImgSel.config.modalId);
    modal.innerHTML = contents;
    document.body.appendChild(modal);
    jmdImgSel.positionModal();
};

/**
 * Positions the modal window.
 */
jmdImgSel.positionModal = function()
{
    var modal = document.getElementById(jmdImgSel.config.modalId);
    if (modal)
    {
        var left = (document.body.clientWidth - jmdImgSel.config.windowWidth)/2;
        modal.style.width = jmdImgSel.config.windowWidth + 'px';
        modal.style.height = jmdImgSel.config.windowHeight + 'px';
        modal.style.left = left + 'px';
    }

    var imgContainer = document.getElementById(jmdImgSel.config.ulId);
    if (imgContainer)
    {
        var controls = document.getElementById(jmdImgSel.config.controlsId);
        var ht = jmdImgSel.config.windowHeight -
            parseInt(jmdImgSel.getStyle(controls, 'margin-bottom')) -
            controls.clientHeight;
        imgContainer.style.height = ht + 'px';
    }
};
jmdImgSel.addEvent(window, 'resize', function(){jmdImgSel.positionModal();});

/**
 * Toggles the modal window's visibility.
 */
jmdImgSel.toggleModal = function()
{
    var overlay = document.getElementById(jmdImgSel.config.overlayId);
    var modal = document.getElementById(jmdImgSel.config.modalId);
    jmdImgSel.toggle(overlay);
    jmdImgSel.toggle(modal);
};

/**
 * Adds event handlers to the select and button.
 * Calls jmdImgSel.prepImg()
 */
jmdImgSel.init = function()
{
    // Close button
    var close = document.getElementById(jmdImgSel.config.closeId);
    if (close)
    {
        jmdImgSel.addEvent(close, 'click', jmdImgSel.toggleModal);
    }

    // Select handler
    var select = document.getElementById(jmdImgSel.config.selectId);
    if (select)
    {
        jmdImgSel.addEvent(select, 'change', function()
        {
            jmdImgSel.sortImg(this.value);
        });
    }

    // Add-images handler
    var button = document.getElementById(jmdImgSel.config.addImgId);
    if (button)
    {
        jmdImgSel.addEvent(button, 'click', jmdImgSel.addImg);
    }

    // Images handler
    var ul = document.getElementById(jmdImgSel.config.ulId);
    var li = ul.getElementsByTagName('li');
    if (li)
    {
        for (var i = 0; i < li.length; i++)
        {
            var id = li[i].id.substring(3);
            if (jmdImgSel.selected.toString().indexOf(id) !== -1)
            {
                li[i].className += ' selected';
            }
            jmdImgSel.addEvent(li[i], 'click', function()
            {
                jmdImgSel.selectImg(this);
            });
            jmdImgSel.addEvent(li[i], 'mouseover', function()
            {
                jmdImgSel.toggleName(this.title);
            });
        }
        jmdImgSel.addEvent(ul, 'mouseout', function()
        {
            jmdImgSel.toggleName();
        });
    }
};

/**
 * Toggle categories based on select value
 *
 * @param string val Category to check against.
 */
jmdImgSel.sortImg = function(val)
{
    var ul = document.getElementById(jmdImgSel.config.ulId);
    if (ul)
    {
        var li = ul.getElementsByTagName('li');
        for (var i = 0; i < li.length; i++)
        {
            if ((li[i].className.match(val)) || (val === 'root'))
            {
                li[i].style.display = '';
            }
            else
            {
                li[i].style.display = 'none';
            }
        }
    }
};

/**
 * Appends or removes the selected class
 * Pushes selected images onto jmdImgSel.selected
 *
 * @param string el #images li[i]
 */
jmdImgSel.selectImg = function(el)
{
    var name = 'selected';
    var id = el.id.substring(3);
    if (el.className.match(new RegExp(name)))
    {
        el.className = el.className.replace(name, '');
        jmdImgSel.unkSplice(jmdImgSel.selected, id);
    }
    else
    {
        el.className += ' ' + name;
        jmdImgSel.selected.push(id);
    }
};


/**
 * Inserts or removes an image name.
 *
 * @param string name Image name
 */
jmdImgSel.toggleName = function(name)
{
    name = (typeof(name) === 'undefined') ? '' : name;
    var nameId = document.getElementById(jmdImgSel.config.imgNameId);
    if (nameId)
    {
        if (name)
        {
            nameId.innerHTML = '&nbsp;' + name;
        }
        else
        {
            nameId.innerHTML = '&nbsp;';
        }
    }
};

/**
 * Fades an element to white, then removes it.
 *
 * @param string el
 * @param int red
 * @param int green
 * @param int blue
 */
jmdImgSel.fadeUp = function(el, red, green, blue)
{
    if (el.fade)
    {
        clearTimeout(el.fade);
    }
    el.style.backgroundColor = 'rgb(' + red + ', ' + green + ', ' + blue + ')';
    if ((red !== 255) || (green !== 255) || (blue !== 255))
    {
        var newRed = colorToWhite(red);
        var newGreen = colorToWhite(green);
        var newBlue = colorToWhite(blue);
        var repeat = function()
        {
            jmdImgSel.fadeUp(el, newRed, newGreen, newBlue);
        };
        el.fade = setTimeout(repeat, 100);
    }
    else
    {
        el.parentNode.removeChild(el);
        return;
    }

    function colorToWhite(orig)
    {
        return orig + (Math.ceil((255 - orig)/10));
    };
};

/**
 * Create TXP tags for each selected image.
 *
 * @param string tagName
 * @param string attr attributes in the form of 'attr="val"'
 */
jmdImgSel.bodyImg = function(tagName, attr)
{
    var out = '';
    for (var i = 0; i < jmdImgSel.selected.length; i++)
    {
        out += '<txp:' + tagName + ' id="' + jmdImgSel.selected[i] + '"' + attr + '/>';
    }
    
    return out;
}

/**
 * Insert image based on the cursor location. Fallback: Append.
 *
 * @param string content Content to be inserted
 */
jmdImgSel.insert = function(content)
{
    var field = document.getElementById(jmdImgSel.config.bodyField);
    // IE
    if (document.selection)
    {
        field.focus();
        document.selection.createRange().text = content;
    }
    // Others
    else if (field.selectionStart || field.selectionStart == 0)
    {
        field.value = field.value.substr(0, field.selectionStart) +
            content + 
            field.value.substring(field.selectionEnd, field.value.length);
    }
    // Append
    else
    {
        field.value += content;
    }
};

/**
 * Update the parent window field
 */
jmdImgSel.addImg = function()
{
    var type = document.getElementById(jmdImgSel.config.typeId);
    switch (type.value)
    {
        case 'article_image':
            var field = document.getElementById(jmdImgSel.config.articleImgField);
            field.value = jmdImgSel.selected.join();
            break;
        case 'body':
            jmdImgSel.insert(jmdImgSel.bodyImg('image', ''));
            break;
        case 'thumbnail':
            jmdImgSel.insert(jmdImgSel.bodyImg('thumbnail', ''));
            break;
        case 'popup':
            jmdImgSel.insert(jmdImgSel.bodyImg('thumbnail', 'poplink="1"'));
            break;
        default:
    }
    var msg = document.getElementById(jmdImgSel.config.updateId);
    if (!msg)
    {
        msg = document.createElement('span');
        msg.id = jmdImgSel.config.updateId;
        msg.appendChild(
            document.createTextNode(jmdImgSel.config.updateMsg)
        );
        var info = document.getElementById(jmdImgSel.config.infoId);
        info.insertBefore(msg, info.firstChild);
    }
    jmdImgSel.fadeUp(msg, 255, 255, 153);
};

jmdImgSel.addEvent(window, 'load', jmdImgSel.insertLink);
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
