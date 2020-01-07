<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
*/

// hook model__thread_start.php

// ------------> 最原生的 CURD，无关联其他数据。

function well_thread__create($arr, $d = NULL)
{
    // hook model__thread__create_start.php
    $r = db_insert('website_thread', $arr, $d);
    // hook model__thread__create_end.php
    return $r;
}

function well_thread__update($tid, $update, $d = NULL)
{
    // hook model__thread__update_start.php
    $r = db_update('website_thread', array('tid' => $tid), $update, $d);
    // hook model__thread__update_end.php
    return $r;
}

function well_thread__read($tid, $orderby = array(), $col = array(), $d = NULL)
{
    // hook model__thread__read_start.php
    $thread = db_find_one('website_thread', array('tid' => $tid), $orderby, $col, $d);
    // hook model__thread__read_end.php
    return $thread;
}

// 最后一条主题
function well_thread_max_tid($col = array('tid'), $d = NULL)
{
    // hook model__thread_max_tid_start.php
    $thread = db_find_one('website_thread', array(), array('tid' => -1), $col, $d);
    // hook model__thread_max_tid_end.php
    return $thread;
}

// 彻底删除
function well_thread__delete($tid, $d = NULL)
{
    // hook model__thread__delete_start.php
    $r = db_delete('website_thread', array('tid' => $tid), $d);
    // hook model__thread__delete_end.php
    return $r;
}

function well_thread__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'tid', $col = array(), $d = NULL)
{
    // hook model__thread__find_start.php
    $threadlist = db_find('website_thread', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model__thread__find_end.php
    return $threadlist;
}

function well_thread_count($cond = array(), $d = NULL)
{
    // hook model__thread_count_start.php
    $n = db_count('website_thread', $cond, $d);
    // hook model__thread_count_end.php
    return $n;
}

//--------------------------强相关--------------------------

function well_thread_create($arr)
{
    global $conf, $time, $longip, $gid, $uid, $forumlist;

    if (empty($arr)) return FALSE;

    // hook model__thread_create_start.php

    $fid = $arr['fid'];
    $message = array_value($arr, 'message');
    $thumbnail = array_value($arr, 'thumbnail', 0); // 获取内容主图
    $delete_pic = array_value($arr, 'delete_pic', 0); // 删除主图
    $save_image = array_value($arr, 'save_image', 0); // 图片本地化
    $doctype = array_value($arr, 'doctype', 0);

    // hook model__thread_create_before.php

    // 创建主题
    $thread = array('fid' => $fid, 'subject' => $arr['subject'], 'type' => $arr['type'], 'brief' => $arr['brief'], 'uid' => $uid, 'create_date' => $time, 'closed' => $arr['closed'], 'keyword' => $arr['keyword'], 'description' => $arr['description'], 'last_date' => $time, 'userip' => $longip, 'attach_on' => $conf['attach_on'], 'flags' => $arr['flags']);

    // hook model__thread_create_thread_after.php

    $upload_thumbnail = well_attach_assoc_type(0); // 缩略图主图
    $upload_file = well_attach_assoc_type(1); // 内容附件

    // hook model__thread_create_center.php

    (empty($delete_pic) AND !empty($upload_thumbnail) OR (empty($delete_pic) AND $thumbnail AND !empty($upload_file))) AND $thread['icon'] = $time;

    // hook model__thread_create_middle.php

    // 主题入库
    $tid = well_thread__create($thread);
    if ($tid === FALSE) return FALSE;
    unset($thread);

    // hook model__thread_create_after.php

    // 关联主图 type 0内容主图 1:内容图片或附件 8:节点主图 9:节点tag主图
    $create_thumbnail = 0;
    if (empty($delete_pic)) {
        // 没上传主图 内容中有上传图片附件
        if (empty($upload_thumbnail) && !empty($upload_file)) {
            // 获取内容第一张图为主图
            $arr = array('tid' => $tid, 'uid' => $uid, 'fid' => $fid);
            // hook model__thread_create_thumbnail_before.php
            $thumbnail AND well_attach_create_thumbnail($arr);
        } elseif (!empty($upload_thumbnail)) {
            // 上传了主图
            $arr = array('tid' => $tid, 'uid' => $uid, 'type' => 0);
            // hook model__thread_create_thumbnail_after.php
            well_attach_assoc_post($arr);
            unset($arr);
        } elseif ($thumbnail) {
            // 获取内容中图片，远程图片下载创建缩略图
            $create_thumbnail = 1;
        }
    }

    // hook model__thread_create_save_image_before.php

    // 图片本地化 并创建缩略图
    ($save_image || $create_thumbnail) AND $message = well_save_remote_image(array('tid' => $tid, 'fid' => $fid, 'uid' => $uid, 'message' => $message, 'thumbnail' => $thumbnail, 'save_image' => $save_image));

    // hook model__thread_create_attach_before.php

    // 关联附件
    $attach = array('tid' => $tid, 'uid' => $uid, 'type' => 1, 'images' => 0, 'files' => 0, 'message' => $message);
    // hook model__thread_create_attach_before.php
    $message = well_attach_assoc_post($attach);
    unset($attach);

    // hook model__thread_create_data_before.php

    // 主题数据入库
    $data = array('tid' => $tid, 'gid' => $gid, 'message' => $message, 'doctype' => $doctype);

    // hook model__thread_create_data_after.php

    $tid = data_create($data);
    if ($tid === FALSE) return FALSE;
    unset($data);

    $forum_update = array('threads+' => 1, 'todaythreads+' => 1);
    // hook model__thread_create_forum_update_before.php
    forum_update($fid, $forum_update);
    unset($forum_update);

    // hook model__thread_create_verify_before.php

    // 我的主题 审核成功写入该表 website_thread_tid表
    if (!group_access($gid, 'publishverify') || group_access($gid, 'managecreatethread')) {

        thread_tid_create(array('tid' => $tid, 'fid' => $fid, 'uid' => $uid));

        // 更新统计数据
        user_update($uid, array('articles+' => 1));

        // hook model__thread_create_verify_middle.php
    } else {
        // 待审核 / Waiting for verification
        // hook model__thread_create_waiting_for_verify.php
    }

    // hook model__thread_create_verify_after.php

    // 全站内容数
    runtime_set('articles+', 1);
    runtime_set('todayarticles+', 1);

    // 删除首页所有缓存
    cache_delete('portal_index_thread');
    cache_delete('portal_channel_thread_' . $fid);

    // hook model__thread_create_end.php

    return $tid;
}

// 仅更新主题表数据和缓存 如更新 tag 等
function well_thread_update($tid, $update)
{
    global $conf;
    // hook model__thread_update_start.php

    if (empty($tid) || empty($update)) return FALSE;

    // hook model__thread_update_before.php

    $r = well_thread__update($tid, $update);
    if ($r === FALSE) return FALSE;

    // hook model__thread_update_after.php

    if ($conf['cache']['type'] != 'mysql') {
        if (is_array($tid)) {
            foreach ($tid as $_tid) cache_delete('website_thread_' . $_tid);
        } else {
            cache_delete('website_thread_' . $tid);
        }
    }

    // hook model__thread_update_end.php

    return $r;
}

// 更新全部数据
function well_thread_update_all($tid, $update)
{
    // hook model__thread_update_all_start.php

    if (empty($tid) || empty($update)) return FALSE;

    // hook model__thread_update_all_before.php

    $r = well_thread_update($tid, $update);
    if ($r === FALSE) return FALSE;

    // hook model__thread_update_all_after.php

    $n = comment_pid_count_by_tid($tid);
    if ($n) {
        $arrlist = comment_pid_find($tid, 1, $n, FALSE);

        $pids = arrlist_values($arrlist, 'pid');

        $r = comment__update($pids, $update);
        if ($r === FALSE) return FALSE;
    }

    // hook model__thread_update_all_end.php

    return $r;
}

// 遍历栏目tid 按照: 发布时间 倒序，不包含置顶
function well_thread_find_tid($fid, $page = 1, $pagesize = 20)
{
    global $forumlist;
    // hook model__thread_find_tid_start.php
    $conf = _SERVER('conf');
    $forum = array_value($forumlist, $fid);
    $threads = $forum['threads'];

    // hook model__thread_find_tid_before.php

    $desc = TRUE;
    $limitpage = 5000; // 如果需要防止 CC 攻击，可以调整为 5000
    if ($page > 100) {
        $totalpage = ceil($threads / $pagesize);
        $halfpage = ceil($totalpage / 2);

        if ($halfpage > $limitpage && $page < ($totalpage - $limitpage)) {
            $page = $limitpage;
        }

        if ($page > $halfpage) {
            $page = max(1, $totalpage - $page + 1);
            $arr = thread_tid_find_by_fid($fid, $page, $pagesize, FALSE);
            $arr = array_reverse($arr, TRUE);
            $desc = FALSE;
        }
    }

    // hook model__thread_find_tid_middle.php

    $desc AND $arr = thread_tid_find_by_fid($fid, $page, $pagesize, TRUE);

    // hook model__thread_find_tid_after.php

    if (empty($arr)) return NULL;

    // hook model__thread_find_tid_end.php

    return $arr;
}

// 按照: rank 倒序，含置顶帖 查询栏目fid下tid 主题数据详情
function well_thread_find_desc($fid, $page = 1, $pagesize = 20)
{
    global $conf, $forumlist;

    // hook model_thread_find_desc_start.php

    $forum = array_value($forumlist, $fid);
    $threads = $forum['threads'];

    // hook model_thread_find_desc_before.php

    $desc = TRUE;
    $limitpage = 5000; // 如果需要防止 CC 攻击，可以调整为 5000
    if ($page > 100) {
        $totalpage = ceil($threads / $pagesize);
        $halfpage = ceil($totalpage / 2);

        if ($halfpage > $limitpage && $page < ($totalpage - $limitpage)) {
            $page = $limitpage;
        }

        if ($page > $halfpage) {
            $page = max(1, $totalpage - $page + 1);
            $arr = thread_tid__find(array('fid' => $fid), array('rank' => 1), $page, $pagesize);
            $arr = array_reverse($arr, TRUE);
            $desc = FALSE;
        }
    }

    $desc AND $arr = thread_tid__find(array('fid' => $fid), array('rank' => -1), $page, $pagesize);

    // hook model_thread_find_desc_after.php

    if (empty($arr)) return NULL;

    // hook model_thread_find_desc_end.php

    return $arr;
}

// 查询用户uid下tid 主题数据详情
function well_thread_find_by_uid($uid, $page = 1, $pagesize = 20)
{
    // hook model__thread_find_by_uid_start.php

    $arr = thread_tid_find_by_uid($uid, $page, $pagesize);

    if (empty($arr)) return NULL;

    // hook model__thread_find_by_uid_before.php

    $tidarr = arrlist_values($arr, 'tid');

    // hook model__thread_find_by_uid_after.php

    $threadlist = well_thread_find($tidarr, $pagesize);

    // hook model__thread_find_by_uid_end.php

    return $threadlist;
}

// tidarr 查询主题数据
// 主题状态0:通过 1~9审核:1待审核 10~19:10退稿 11逻辑删除
function well_thread_find($tidarr, $pagesize = 20, $desc = TRUE)
{
    // hook model__thread_find_start.php
    $orderby = $desc == TRUE ? -1 : 1;
    $threadlist = well_thread__find(array('tid' => $tidarr), array('tid' => $orderby), 1, $pagesize);

    // hook model__thread_find_before.php

    if ($threadlist) {
        $i = 0;
        foreach ($threadlist as &$thread) {
            ++$i;
            $thread['i'] = $i;
            well_thread_format($thread);
            // hook model__thread_find_format_after.php
        }
    }

    // hook model__thread_find_end.php

    return $threadlist;
}

// tidarr 查询主题数据 不给mysql增加压力使用正序 倒叙可以使用array_reverse($threadlist, TRUE);
// 主题状态0:通过 1~9审核:1待审核 10~19:10退稿 11逻辑删除
function well_thread_find_asc($tidarr, $pagesize = 20)
{
    // hook model__thread_find_start.php

    $threadlist = well_thread__find(array('tid' => $tidarr), array('tid' => 1), 1, $pagesize);

    // hook model__thread_find_before.php

    if ($threadlist) {
        foreach ($threadlist as &$thread) {
            well_thread_format($thread);
            // hook model__thread_find_format_after.php
        }
    }

    // hook model__thread_find_end.php

    return $threadlist;
}

function well_thread_find_by_tids($tidarr)
{
    // hook model__thread_find_by_tids_start.php
    $threadlist = well_thread_find($tidarr, 1000);
    // hook model__thread_find_by_tids_end.php
    return $threadlist;
}

// views + 1 大站可以单独剥离出来
function well_thread_inc_views($tid, $n = 1)
{
    global $conf, $db;
    // hook model__thread_inc_views_start.php
    $tablepre = $db->tablepre;
    //if (!$conf['update_views_on']) return TRUE;
    $sqladd = in_array($conf['cache']['type'], array('mysql', 'pdo_mysql')) ? ' LOW_PRIORITY' : '';
    $r = db_exec("UPDATE$sqladd `{$tablepre}website_thread` SET views=views+$n WHERE tid='$tid'");
    // hook model__thread_inc_views_end.php
    return $r;
}

function well_thread_read($tid)
{
    // hook model__thread_read_start.php
    $thread = well_thread__read($tid);
    $thread AND well_thread_format($thread);
    // hook model__thread_read_end.php
    return $thread;
}

// 只删除主题和缓存
function well_thread_delete($tid)
{
    global $conf;

    if (empty($tid)) return FALSE;

    // hook model__thread_delete_start.php

    $r = well_thread__delete($tid);
    if ($r === FALSE) return FALSE;

    if (is_array($tid) && $conf['cache']['type'] != 'mysql') {
        if (is_array($tid)) {
            foreach ($tid as $_tid) cache_delete('website_thread_' . $_tid);
        } else {
            cache_delete('website_thread_' . $tid);
            runtime_set('articles-', 1);
        }
    }

    // hook model__thread_delete_end.php

    return $r;
}

// 删除主题相关的全部数据 TODO 删除数据太多，可能导致超时，有时间再重写
function well_thread_delete_all($tid)
{
    global $conf;

    if (empty($tid)) return FALSE;

    // hook model_thread_delete_all_start.php

    $thread = well_thread_read_cache($tid);
    if (empty($thread)) return FALSE;

    // hook model_thread_delete_all_before.php

    $forumupdate = array('threads-' => 1);
    // hook model_thread_delete_all_icon_before.php

    // 删除主图
    if ($thread['icon']) {
        $attach_dir_save_rule = array_value($conf, 'well_attach_dir_save_rule', 'Ym');
        $day = date($attach_dir_save_rule, $thread['icon']);
        $file = $conf['upload_path'] . 'thumbnail/' . $day . '/' . $thread['uid'] . '_' . $thread['tid'] . '_' . $thread['icon'] . '.jpeg';
        is_file($file) AND unlink($file);
    }

    // hook model_thread_delete_all_tag_before.php

    // 删除tag
    if ($thread['tag']) {
        $tagids = array_keys($thread['tag_text']);
        well_oldtag_delete($tagids, $tid);
    }

    // hook model_thread_delete_all_sticky_before.php

    // 删除置顶
    if ($thread['sticky']) {
        $r = sticky_thread__delete($tid);
        if ($r === FALSE) return FALSE;
        $forumupdate['tops-'] = 1;
        cache_delete('sticky_thread_list');
    }

    // 删除主题属性 同时更新
    if ($thread['flags']) {
        $r = flag_thread_delete_by_tid($tid);
        if ($r === FALSE) return FALSE;
    }

    // hook model_thread_delete_all_data_before.php

    // 删除内容
    $r = data_delete($tid);
    if ($r === FALSE) return FALSE;

    // hook model_thread_delete_all_post_before.php

    // 删除所有回复 同时更新了用户评论数
    $n = comment_delete_by_tid($tid);

    // hook model_thread_delete_all_attach_before.php

    // 删除附件
    ($thread['images'] || $thread['files']) && well_attach_delete_by_tid($tid);

    // hook model_thread_delete_all_tid_before.php

    // 删除主题
    $r = well_thread_delete($tid);
    if ($r === FALSE) return FALSE;

    $r = thread_tid_delete($tid);
    if ($r === FALSE) return FALSE;

    user_update($thread['uid'], array('articles-' => 1));

    // hook model_thread_delete_all_forum_update_before.php

    // 更新统计
    forum_update($thread['fid'], $forumupdate);

    // hook model_thread_delete_all_runtime_set_before.php

    // 实时缓存 全站统计
    runtime_set('articles-', 1);

    // hook model__thread_delete_all_end.php

    return $r;
}

// 大数据量容易超时 删除用户时使用，删除主题 回复 栏目统计 附件 全站统计
function well_thread_delete_all_by_uid($uid)
{
    global $conf, $user;

    // hook model__thread_delete_all_by_uid_start.php

    // 统计用户主题数 海量数据不推荐使用
    $n = thread_uid_count($uid);

    // hook model__thread_delete_all_by_uid_before.php

    $tidarr = array();
    $forum_tids = array();
    if ($n) {
        // 如果主题、附件和回复数量太大可能会超时
        $tidlist = thread_tid_find_by_uid($uid, 1, $n, FALSE, 'tid', array('fid', 'tid'));
        foreach ($tidlist as $val) {
            // 每个栏目下的主题数
            $forum_tids[$val['tid']] = $val['fid'];
            $tidarr[] = $val['tid'];
        }

        unset($tidlist);

        $threadlist = well_thread__find(array('tid' => $tidarr), array('tid' => 1), 1, $n, 'tid', array('icon', 'images', 'files'));

        $stickytid = array();
        foreach ($threadlist as $thread) {
            // 删除主图
            if ($thread['icon']) {
                $attach_dir_save_rule = array_value($conf, 'well_attach_dir_save_rule', 'Ym');
                $day = date($attach_dir_save_rule, $thread['icon']);
                $file = $conf['upload_path'] . 'thumbnail/' . $day . '/' . $thread['uid'] . '_' . $thread['tid'] . '_' . $thread['icon'] . '.jpeg';
                is_file($file) AND unlink($file);
            }

            $thread['sticky'] AND $stickytid[] = $thread['tid'];
        }

        // hook model__thread_delete_all_by_uid_center.php

        // 清理置顶
        empty($stickytid) || sticky_thread__delete($stickytid);
    }

    // 删除所有回复
    $posts = comment_pid_count_by_uid($uid);
    $pidarr = array();
    $forum_pids = array();
    if ($posts) {
        $postist = comment_pid_find_by_uid($uid, 1, $posts, FALSE);

        foreach ($postist as $val) {
            // 每个栏目下的回复数
            $forum_pids[$val['pid']] = $val['fid'];
            $pidarr[] = $val['pid'];
        }
        unset($postist);

        comment_delete($pidarr);
    }

    // hook model__thread_delete_all_by_uid_middle.php

    if (empty($tidarr)) return FALSE;

    // 更新统计
    if (!empty($forum_tids)) {
        $forum_tids = array_count_values($forum_tids);
        // 回复数 版块表以及删除此字段
        //$forum_pids = array_count_values($forum_pids);
        // hook model__thread_delete_all_forum_update_before.php
        foreach ($forum_tids as $k => $v) {
            $update = array('threads-' => $v);
            //!empty($forum_pids) AND $update['posts'] = $forum_pids[$k];
            // hook model__thread_delete_all_forum_update_middle.php
            forum_update($k, $update);
        }
        // hook model__thread_delete_all_forum_update_after.php
    }

    // 删除主题
    $r = well_thread_delete($tidarr);
    if ($r === FALSE) return FALSE;

    // 删除主题小表
    $r = thread_tid_delete($tidarr);
    if ($r === FALSE) return FALSE;

    // hook model__thread_delete_all_by_uid_after.php

    $threads = count($tidarr);
    $posts = count($pidarr);
    // hook model__thread_delete_all_by_uid_runtime_before.php
    // 实时缓存 全站统计
    runtime_set('articles-', $threads);
    runtime_set('comments-', $posts);

    // hook model__thread_delete_all_by_uid_end.php

    return TRUE;
}

// 搜索标题
function well_thread_find_by_keyword($keyword)
{
    global $db;
    if (empty($keyword)) return NULL;

    $tablepre = $db->tablepre;
    // hook model__thread_find_by_keyword_start.php

    $threadlist = db_sql_find("SELECT * FROM `{$tablepre}website_thread` WHERE subject LIKE '%$keyword%' LIMIT 60;");

    // hook model__thread_find_by_keyword_before.php

    if ($threadlist) {
        $threadlist = arrlist_multisort($threadlist, 'tid', FALSE);

        // hook model__thread_find_by_keyword_after.php
        foreach ($threadlist as &$thread) {
            well_thread_format($thread);
            // 关键词标色
            //$thread['subject'] = comment_highlight_keyword($thread['subject'], $keyword);
        }
    }

    // hook model__thread_find_by_keyword_end.php

    return $threadlist;
}

// 查找 最后评论 lastpid
function well_thread_find_lastpid($tid)
{
    $arr = comment_pid_read(array('tid' => $tid), array('pid' => -1), array('pid'));
    $lastpid = empty($arr) ? 0 : $arr['pid'];
    return $lastpid;
}

// 更新最后的 uid
function well_thread_update_last($tid)
{
    if (empty($tid)) return FALSE;

    $lastpid = well_thread_find_lastpid($tid);
    if (empty($lastpid)) return FALSE;

    $lastpost = comment_read($lastpid);
    if (empty($lastpost)) return FALSE;

    $r = well_thread_update($tid, array('lastuid' => $lastpost['uid']));

    return $r;
}

function well_thread_maxid()
{
    // hook model__thread_maxid_start.php
    $n = db_maxid('website_thread', 'tid');
    // hook model__thread_maxid_end.php
    return $n;
}

// 主题状态 0:通过 1~9 审核:1待审核 10~19:10退稿 11逻辑删除
function well_thread_format(&$thread)
{
    global $gid, $uid, $forumlist;
    $conf = _SERVER('conf');
    if (empty($thread)) return;

    // hook model__thread_format_start.php

    $thread['create_date_fmt'] = humandate($thread['create_date']);
    $thread['last_date_fmt'] = humandate($thread['last_date']);
    $thread['create_date_text'] = date('Y-m-d', $thread['create_date']);
    $thread['last_date_text'] = date('Y-m-d', $thread['last_date']);

    $user = user_read_cache($thread['uid']);
    $thread['username'] = $user['username'];
    $thread['user_avatar_url'] = $user['avatar_url'];
    $thread['user'] = user_safe_info($user);
    unset($user);

    $forum = isset($forumlist[$thread['fid']]) ? $forumlist[$thread['fid']] : array('name' => '');
    $thread['forum_name'] = $forum['name'];

    if ($thread['last_date'] == $thread['create_date']) {
        $thread['last_date_fmt'] = '';
        $thread['lastuid'] = 0;
        $thread['lastusername'] = '';
    } else {
        $lastuser = $thread['lastuid'] ? user_read_cache($thread['lastuid']) : array();
        $thread['lastusername'] = $thread['lastuid'] ? $lastuser['username'] : lang('guest');
    }

    $thread['url'] = url('read-' . $thread['tid']);

    $thread['user_url'] = url('user-' . $thread['uid']);

    $thread['sticky_class'] = '';
    if ($thread['sticky'] > 0) {
        if ($thread['sticky'] == 1) {
            $thread['sticky_class'] = 'success';
        } elseif ($thread['sticky'] == 2) {
            $thread['sticky_class'] = 'warning';
        } elseif ($thread['sticky'] == 3) {
            $thread['sticky_class'] = 'danger';
        }
    }

    $nopic = view_path() . 'img/nopic.png';

    if ($thread['icon']) {

        $attach_dir_save_rule = array_value($conf, 'well_attach_dir_save_rule', 'Ym');
        $day = date($attach_dir_save_rule, $thread['icon']);

        // 本地文件绝对路径
        $destfile = $conf['upload_path'] . 'thumbnail/' . $day . '/' . $thread['uid'] . '_' . $thread['tid'] . '_' . $thread['icon'] . '.jpeg';

        $thread['icon_text'] = is_file($destfile) ? file_path() . 'thumbnail/' . $day . '/' . $thread['uid'] . '_' . $thread['tid'] . '_' . $thread['icon'] . '.jpeg' : $nopic;

        if ($conf['attach_on'] == 2 && $thread['attach_on'] == 2) {
            // 开启图床 没有上传成功 本地图片在的话使用本地，不在则使用默认
            $thread['icon_text'] = $thread['image_url'] ? $thread['image_url'] : $thread['icon_text'];
        }
    } else {
        $thread['icon_text'] = $nopic;
    }

    // 回复页面
    $thread['pages'] = ceil($thread['posts'] / $conf['postlist_pagesize']);

    $thread['tag_text'] = $thread['tag'] ? xn_json_decode($thread['tag']) : '';

    // SEO描述 此处格式会导致编辑时也调用到该数据
    //$thread['description'] = $thread['description'] ? $thread['description'] : ($thread['type']==10 ? '' : $thread['brief']);

    // 权限判断
    $thread['allowupdate'] = ($uid == $thread['uid']) || forum_access_mod($thread['fid'], $gid, 'allowupdate');
    $thread['allowdelete'] = ($uid == $thread['uid']) || forum_access_mod($thread['fid'], $gid, 'allowdelete');

    // hook model__thread_format_end.php
}

function well_thread_format_last_date(&$thread)
{
    // hook model__thread_format_last_date_start.php
    if ($thread['last_date'] != $thread['create_date']) {
        $thread['last_date_fmt'] = humandate($thread['last_date']);
    } else {
        $thread['create_date_fmt'] = humandate($thread['create_date']);
    }
    // hook model__thread_format_last_date_end.php
}

// 对 $threadlist 权限过滤
function well_thread_list_access_filter(&$threadlist, $gid)
{
    global $forumlist;

    if ($threadlist == NULL) return NULL;

    // hook model__thread_list_access_filter_start.php

    foreach ($threadlist as $tid => $thread) {
        if (empty($forumlist[$thread['fid']]['accesson'])) continue;
        if ($thread['sticky'] > 0) continue;
        if (!forum_access_user($thread['fid'], $gid, 'allowread')) {
            unset($threadlist[$tid]);
        }
    }

    // hook model__thread_list_access_filter_end.php
}

function well_thread_safe_info($thread)
{
    // hook model__thread_safe_info_start.php

    unset($thread['userip']);
    unset($thread['user']['threads']);
    unset($thread['user']['posts']);
    unset($thread['user']['credits']);
    unset($thread['user']['golds']);
    unset($thread['user']['rmbs']);
    unset($thread['user']['golds']);

    empty($thread['user']) || $thread['user'] = user_safe_info($thread['user']);

    // hook model__thread_safe_info_end.php

    return $thread;
}

// 过滤安全数据
function well_thread_filter(&$val)
{
    // hook well_thread_filter_start.php
    unset($val['userip']);
    unset($val['fid']);
    unset($val['flagid']);
    unset($val['type']);
    unset($val['user']);
    unset($val['create_date']);
    // hook well_thread_filter_end.php
}

//------------------------ 其他方法 ------------------------
// 后台和前台删除内容 并写入日志
function well_thread_delete_content($tid)
{
    global $time, $uid, $gid;

    // hook model_thread_delete_content_start.php

    $thread = well_thread_read_cache($tid);
    empty($thread) AND message(-1, lang('thread_not_exists'));

    // hook model_thread_delete_content_before.php

    // 权限判断 仅限管理员和用户本人有权限
    $allowdelete = ($uid == $thread['uid']) || forum_access_mod($thread['fid'], $gid, 'allowdelete');

    (empty($allowdelete) OR $thread['closed']) AND message(-1, lang('thread_has_already_closed'));

    $delete_from_default = 0;

    // hook model_thread_delete_content_center.php

    // 默认删除全部
    if ($delete_from_default == 0) {
        // 全部删除
        well_thread_delete_all($tid) === FALSE AND message(-1, lang('delete_failed'));
    }

    // hook model_thread_delete_content_middle.php

    $arr = array('type' => 1, 'uid' => $uid, 'tid' => $tid, 'subject' => $thread['subject'], 'comment' => '', 'create_date' => $time);

    // hook model_thread_delete_content_after.php

    // 创建日志
    $r = operate_create($arr);

    // hook model_thread_delete_content_end.php

    return $r;
}

// 集合主题tid，统一拉取，避免多次查询thread表
function thread_unified_pull($arr)
{
    global $gid, $fid;

    // hook model_thread_unified_pull_start.php

    $stickylist = array_value($arr, 'stickylist', array());
    $tidlist = array_value($arr, 'tidlist', array());
    //$fid = array_value($arr, 'fid');

    // hook model_thread_unified_pull_before.php

    // 合并过滤空数组
    //$tidlist = array_filter($stickylist + $tidlist);
    $tidarrlist = $tidlist = $stickylist + $tidlist;

    // hook model_thread_unified_pull_center.php

    // 版块自定义
    list($flaglist, $flagtids) = flag_thread_by_fid($fid);
    empty($flagtids) || $tidarrlist += $flagtids;
    unset($flagtids);

    // hook model_thread_unified_pull_merge_before.php
    // 在这之前合并所有二维数组 tid值为键/array('tid值' => tid值)
    $tidarr = empty($tidarrlist) ? array() : arrlist_values($tidarrlist, 'tid');
    // 在这之前使用array_merge()前合并所有一维数组 tid/array(1,2,3)
    // hook model_thread_unified_pull_merge_after.php

    if (empty($tidarr)) return NULL;

    // 主题相关统一遍历后再归类
    $arrlist = well_thread_find(array_unique($tidarr), count($tidarr));

    // 过滤没有权限访问的主题 / filter no permission thread
    well_thread_list_access_filter($arrlist, $gid);

    $threadlist = array();
    foreach ($arrlist as $_tid => &$_thread) {
        // 归类列表数据
        isset($tidlist[$_thread['tid']]) AND $threadlist[$_tid] = well_thread_safe_info($_thread);

        // hook model_thread_unified_pull_threadlist.php

        // flag thread
        if (!empty($flaglist)) {
            foreach ($flaglist as $key => $val) {
                if (in_array($_thread['tid'], $val['tids'])) {
                    $flaglist[$key]['list'][array_search($_thread['tid'], $val['tids'])] = $_thread;
                    ksort($flaglist[$key]['list']);
                    // hook model_thread_unified_pull_flag.php
                }
            }
        }

        // hook model_thread_unified_pull_flaglist.php
    }

    unset($arrlist);

    // hook model_thread_unified_pull_middle.php
    // 按之前tidlist排序
    $threadlist = array2_sort_key($threadlist, $tidlist, 'tid');
    unset($tidlist);

    // hook model_thread_unified_pull_after.php

    $arr = array('threadlist' => $threadlist, 'flaglist' => $flaglist);

    // hook model_thread_unified_pull_end.php

    return $arr;
}

// read.php 详情页其他主题调用，集合tid统一拉取数据，最后归类
function thread_other_pull($thread)
{
    global $forumlist, $gid;

    // hook model_thread_other_pull_start.php

    $fid = array_value($thread, 'fid');
    $forum = array_value($forumlist, $fid);

    if (empty($forum)) return NULL;
    //$tid = array_value($thread, 'tid');
    //$tag_text = array_value($thread, 'tag_text');

    // hook model_thread_other_pull_before.php

    $arrlist = array();
    $tidlist = array();

    // hook model_thread_other_pull_center.php

    // 版块自定义
    list($flaglist, $flagtids) = flag_thread_by_fid($fid);
    empty($flagtids) || $tidlist += $flagtids;
    unset($flagtids);

    // hook model_thread_other_pull_middle.php
    // 在这之前合并所有二维数组 tid值为键/array('tid值' => tid值)
    $tidarr = empty($tidlist) ? array() : arrlist_values($tidlist, 'tid');
    // 在这之前使用array_merge()前合并所有一维数组 tid/array(1,2,3)
    // hook model_thread_other_pull_after.php

    if (empty($tidarr)) return NULL;

    // 主题相关统一遍历后再归类
    $threadlist = well_thread_find(array_unique($tidarr), count($tidarr));

    // 过滤没有权限访问的主题 / filter no permission thread
    well_thread_list_access_filter($threadlist, $gid);

    foreach ($threadlist as &$_thread) {

        // hook model_thread_other_pull_cate_before.php

        // flag thread
        if (!empty($flaglist)) {
            foreach ($flaglist as $key => $val) {
                if (in_array($_thread['tid'], $val['tids'])) {

                    $flaglist[$key]['list'][array_search($_thread['tid'], $val['tids'])] = $_thread;

                    ksort($flaglist[$key]['list']);

                    // hook model_thread_other_pull_flag.php
                }
            }
        }
        // hook model_thread_other_pull_cate_after.php
    }

    // hook model_thread_other_pull_threadlist_after.php
    unset($threadlist);

    if (!empty($flaglist)) {
        foreach ($flaglist as &$val) {
            $i = 0;
            if (!isset($val['list'])) continue;
            foreach ($val['list'] as &$v) {
                ++$i;
                $v['i'] = $i;
            }
        }
        $arrlist['flaglist'] = $flaglist;
        unset($flaglist);
    }

    // hook model_thread_other_pull_end.php

    return $arrlist;
}

//--------------------------cache--------------------------
// 已格式化 从缓存中读取，避免重复从数据库取数据
function well_thread_read_cache($tid)
{
    global $conf;
    // hook model__thread_read_cache_start.php
    $key = 'website_thread_' . $tid;
    static $cache = array(); // 用静态变量只能在当前 request 生命周期缓存，跨进程需要再加一层缓存：redis/memcached/xcache/apc
    if (isset($cache[$key])) return $cache[$key];
    if ($conf['cache']['type'] == 'mysql') {
        $r = well_thread_read($tid);
    } else {
        $r = cache_get($key);
        if ($r === NULL) {
            $r = well_thread_read($tid);
            $r AND cache_set($key, $r, 1800);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    // hook model__thread_read_cache_end.php
    return $cache[$key];
}

// hook model__thread_end.php

?>