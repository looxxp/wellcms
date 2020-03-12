/* 搜索使用 */
$('#form-search').on('submit', function() {
    var jthis = $(this);
    var range = jthis.find('input[name="range"]').val();
    var keyword = jthis.find('input[name="keyword"]').val();
    window.location = xn.url('operate-search-' + xn.urlencode(keyword) + '-' + range);
    return false;
});

/*表单快捷键提交 CTRL+ENTER   / form quick submit*/
$('form').keyup(function (e) {
    var jthis = $(this);
    if ((e.ctrlKey && (e.which == 13 || e.which == 10)) || (e.altKey && e.which == 83)) {
        jthis.trigger('submit');
        return false;
    }
});

/*点击响应整行：方便手机浏览  / check response line*/
$('.tap').on('click', function (e) {
    var href = $(this).attr('href') || $(this).data('href');
    if (e.target.nodeName == 'INPUT') return true;
    if ($(window).width() > 992) return;
    if (e.ctrlKey) {
        window.open(href);
        return false;
    } else {
        window.location = href;
    }
});

/*点击响应整行：导航栏下拉菜单   / check response line*/
$('ul.nav > li').on('click', function (e) {
    var jthis = $(this);
    var href = jthis.children('a').attr('href');
    if (e.ctrlKey) {
        window.open(href);
        return false;
    }
});

/*点击响应整行：，但是不响应 checkbox 的点击  / check response line, without checkbox*/
$('.thread input[type="checkbox"]').parents('td').on('click', function (e) {
    e.stopPropagation();
});

/*管理用户组*/
$('.admin-manage-user').on('click', function () {
    var href = $(this).data('href');
    $.xpost(href, function (code, message) {
        if (code == 0) {
            $.alert(message).delay(1000).location();
        } else {
            $.alert(message).delay(2000).location();
        }
    });
    return false;
});

/*菜单右至左滑出*/
$('.button-show').click(function () {
    $(this).css("display", "none");
    $(this).removeClass('d-lg-none position-fixed rounded-left bg-secondary d-flex align-items-center');
    var nav = $('#nav-show');
    nav.css({"top": "0", "bottom": "0", "z-index": "1020"});
    nav.removeClass('d-none d-lg-block');
    nav.find('.post-sticky-top').removeClass('sticky-top pt-2');
    nav.find('.post-sticky-top').addClass('pt-5 px-2');
    nav.addClass('position-fixed col-9 offset-3 h-100 bg-white p-0');
    nav.animate({right: ""}, 500);
    return false;
});

/*菜单左至右隐藏*/
$('.button-hide').click(function () {
    $(this).css("display", "none");
    var show = $('.button-show');
    show.addClass('d-lg-none position-fixed rounded-left bg-secondary d-flex align-items-center');
    show.css("display", "block");
    var nav = $('#nav-show');
    nav.removeClass("top", "bottom", "z-index");
    nav.removeClass('position-fixed col-9 offset-3 h-100 bg-white p-0');
    nav.find('.post-sticky-top').removeClass('pt-5 px-2');
    nav.find('.post-sticky-top').addClass('sticky-top pt-2');
    nav.addClass('d-none d-lg-block');
    nav.animate({left: ""}, 500);
    return false;
});

/*tag*/
$(function () {
    var tag_input = $('.tag-input');
    tag_input.val('');

    $(document).on('keydown', '.tag-input', function (event) {
        var tag_input = $(this);
        var token = tag_input.parents('.tags').find('.tags-token');
        if (event.keyCode == 13 || event.keyCode == 108 || event.keyCode == 188 || event.keyCode == 32) {
            create_tag();
            return false;
        }
        var str = tag_input.val().replace(/\s+/g, '');
        if (str.length == 0 && event.keyCode == 8) {
            if (token.length >= 1) {
                tag_input.parents('.tags').find('.tags-token:last').remove();
                get_tag_val(tag_input);
                return false;
            }
        }
    });

    $(document).on('click', '.tags-token', function () {
        var it = $(this).parents('.tags');
        $(this).remove();
        var str = '';
        var token = it.find('.tags-token');
        if (token.length < 1) {
            it.find('.tags-val').val('');
            return false;
        }
        for (var i = 0; i < token.length; i++) {
            str += token.eq(i).text() + ',';
            it.find('.tags-val').val(str);
        }
    });

    tag_input.bind("input propertychange", function () {
        var str = $(this).val();
        if (str.indexOf(',') != -1 || str.indexOf('，') != -1 || str.indexOf(' ') != -1) {
            create_tag();
            return false;
        }
    });

    function create_tag() {
        var tag_input = $('.tag-input');
        var str = tag_input.val().replace(/\s+/g, '');
        var reg = new RegExp("[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）——|{}【】‘；：”“'。，、？%]", 'g');
        str = str.replace(reg, '');
        if (str.length > 0) {
            var tags = $('input[name="tags"]').val();
            n = xn.strpos(tags, str);
            if (n >= 0) {
                tag_input.val('');
                return false;
            }
            var tagarr = xn.explode(',', tags);
            if (Object.count(tagarr) <= 5) {
                $('<span class="border border-secondary tag btn-sm my-1 mr-3 tags-token">' + str + '</span>').insertBefore(tag_input.parents(".tags").find(".tag-wrap"));
            }
            tag_input.val('');
            get_tag_val(tag_input);
        }
    }

    function get_tag_val(obj) {
        var str = '';
        var token = $(obj).parents('.tags').find('.tags-token');
        if (token.length < 1) {
            $(obj).parents('.tags').find('.tags-val').val('');
            return false;
        }
        for (var i = 0; i < token.length; i++) {
            str += token.eq(i).text() + ',';
            str = str.replace(/\s+/g, '');
            $(obj).parents('.tags').find('.tags-val').val(str);
        }
    }
});

/* 导航子菜单 鼠标悬浮移除移入*/
$(function () {
    /*var dropdown = $(".dropdown");
    dropdown.mouseover(function () {
        $(this).addClass("show");
        $(this).children('a.dropdown-toggle').attr("aria-expanded", "true");
        $(this).find('.dropdown-menu').addClass("show");
    });
    dropdown.mouseleave(function () {
        $(this).removeClass("show");
        $(this).children('a.dropdown-toggle').attr("aria-expanded", "false");
        $(this).find('.dropdown-menu').removeClass("show");
    });
    dropdown.click(function () {
        var v = $(this).children('a.dropdown-toggle').attr("aria-expanded");
        if (v == false) {
            $(this).addClass("show");
            $(this).children('a.dropdown-toggle').attr("aria-expanded", "true");
            $(this).find('.dropdown-menu').addClass("show");
        } else {
            $(this).removeClass("show");
            $(this).children('a.dropdown-toggle').attr("aria-expanded", "false");
            $(this).find('.dropdown-menu').removeClass("show");
        }
    });*/
});

/*
 确定框 / confirm / GET / POST
 <a href="1.php" data-confirm-text="确定删除？" class="confirm">删除</a>
 <a href="1.php" data-method="post" data-confirm-text="确定删除？" class="confirm">删除</a>
 */
$('a.confirm').on('click', function () {
    var jthis = $(this);
    var text = jthis.data('confirm-text');
    $.confirm(text, function () {
        var method = xn.strtolower(jthis.data('method'));
        var href = jthis.data('href') || jthis.attr('href');
        if (method == 'post') {
            $.xpost(href, function (code, message) {
                if (code == 0) {
                    window.location.reload();
                } else {
                    alert(message);
                }
            });
        } else {
            window.location = jthis.attr('href');
        }
    })
    return false;
});

/*选中所有 / check all
 <input class="checkall" data-target=".tid" />*/
$('input.checkall').on('click', function () {
    var jthis = $(this);
    var target = jthis.data('target');
    jtarget = $(target);
    jtarget.prop('checked', this.checked);
});

/*
 jmobile_collapsing_bavbar = $('#mobile_collapsing_bavbar');
 jmobile_collapsing_bavbar.on('touchstart', function(e) {
 //var h = $(window).height() - 120;
 var h = 350;
 jmobile_collapsing_bavbar.css('overflow-y', 'auto').css('max-height', h+'px');
 e.stopPropagation();
 });
 jmobile_collapsing_bavbar.on('touchmove', function(e) {
 //e.stopPropagation();
 //e.stopImmediatePropagation();
 });*/

/*引用 / Quote*/
var body = $('body');
body.on('click', '.post_reply', function () {
    var jthis = $(this);
    var tid = jthis.data('tid');
    var pid = jthis.data('pid');
    var jmessage = $('#message');
    var jli = jthis.closest('.post');
    var jpostlist = jli.closest('.postlist');
    var jadvanced_reply = $('#advanced_reply');
    var jform = $('#quick_reply_form');
    if (jli.hasClass('quote')) {
        jli.removeClass('quote');
        jform.find('input[name="quotepid"]').val(0);
        jadvanced_reply.attr('href', xn.url('post-create-' + tid));
    } else {
        jpostlist.find('.post').removeClass('quote');
        jli.addClass('quote');
        var s = jmessage.val();
        jform.find('input[name="quotepid"]').val(pid);
        jadvanced_reply.attr('href', xn.url('post-create-' + tid + '-0-' + pid));
    }
    jmessage.focus();
    return false;
});

/* BBS 删除 / Delete post*/
body.on('click', '.post_delete', function () {
    var jthis = $(this);
    var href = jthis.data('href');
    var isfirst = jthis.attr('isfirst');
    if (window.confirm(lang.confirm_delete)) {
        $.xpost(href, {safe_token: safe_token}, function (code, message) {
            var isfirst = jthis.attr('isfirst');
            if (code == 0) {
                if (isfirst == 1) {
                    window.location = forum_url;
                } else {
                    // 删掉楼层
                    jthis.parents('.post').remove();
                    // 回复数 -1
                    var jposts = $('.posts');
                    jposts.html(xn.intval(jposts.html()) - 1);
                }
            } else {
                $.alert(message);
            }
        });
    }
    return false;
});

body.on('click', '.install, .uninstall', function () {
    var href = $(this).data('href');
    $.xpost(href, function (code, message) {
        if (code == 0) {
            $.alert(message).delay(1000).location();
        } else {
            $.alert(message);
        }
    });
    return false;
});