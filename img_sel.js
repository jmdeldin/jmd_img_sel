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
