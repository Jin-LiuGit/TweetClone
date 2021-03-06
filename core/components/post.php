<?php 
# @*************************************************************************@
# @ @author Mansur Altamirov (Mansur_TL)                                    @
# @ @author_url 1: https://www.instagram.com/mansur_tl                      @
# @ @author_url 2: http://codecanyon.net/user/mansur_tl                     @
# @ @author_email: highexpresstore@gmail.com                                @
# @*************************************************************************@
# @ ColibriSM - The Ultimate Modern Social Media Sharing Platform           @
# @ Copyright (c) 21.03.2020 ColibriSM. All rights reserved.                @
# @*************************************************************************@

function cl_create_orphan_post($user_id = null, $type = "text") {
	global $db;

	if (not_num($user_id)) {
		return false;
	}

	$id           =  $db->insert(T_PUBS, array(
		"user_id" => $user_id,
		"status"  => "orphan",
		"type"    => $type
	));

	return (is_posnum($id)) ? $id : 0;
}

function cl_get_orphan_post($id = null) {
	global $db;

	if (not_num($id)) {
		return false;
	}

	$db           = $db->where("id", $id);
	$db           = $db->where("status", "orphan");
	$query_result = $db->getOne(T_PUBS);
	$data         = array();

	if (cl_queryset($query_result)) {
		if (in_array($query_result['type'], array("image","video"))) {
			$media = cl_get_post_media($id);

			if (cl_queryset($media)) {
				$query_result['media'] = $media;
			}
		}

		$data = $query_result;
	}

	return $data;
}

function cl_get_post_media($post_id = false) {
	global $db;

	if (not_num($post_id)) {
		return array();
	}

	$db   = $db->where("pub_id",$post_id);
	$qr   = $db->get(T_PUBMEDIA);
	$data = array();

	if (cl_queryset($qr)) {
		foreach ($qr as $row) {
			$row['x'] = json($row['json_data']);
			$data[]   = $row;
		}
	}

	return $data;
}

function cl_delete_orphan_posts($user_id = null) {
	global $db;

	if (not_num($user_id)) {
		return false;
	}

	$db           = $db->where("user_id", $user_id);
	$db           = $db->where("status", "orphan");
	$query_result = $db->get(T_PUBS);
	$data         = array();

	if (cl_queryset($query_result)) {

		foreach ($query_result as $row) {
			if (in_array($row['type'], array("image", "video"))) {
				$media = cl_get_post_media($row['id']);

				if (cl_queryset($media)) {
					foreach ($media as $media_data) {
						if (in_array($media_data['type'], array('image','video'))) {
           
		                    $json_data = json($media_data['json_data']);

		                    cl_delete_media($media_data['src']);

		                    if (not_empty($json_data['image_thumb'])) {
		                        cl_delete_media($json_data['image_thumb']);
		                    }
		                    else if(not_empty($json_data['poster_thumb'])){
		                    	cl_delete_media($json_data['poster_thumb']);
		                    }
		                }
					}

					$db->where("pub_id",$row['id'])->delete(T_PUBMEDIA);
				}
			}
		}

		$db->where("user_id", $user_id)->where("status", "orphan")->delete(T_PUBS);
	}
}

function cl_update_post_data($post_id = null, $data = array()) {
    global $db;
    if ((not_num($post_id)) || (empty($data) || is_array($data) != true)) {
        return false;
    } 

    $db     = $db->where('id', $post_id);
    $update = $db->update(T_PUBS,$data);
    return ($update == true) ? true : false;
}

function cl_upsert_htags($text = "") {
	global $db;

	if (not_empty($text)) {

		preg_match_all('/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]*\s]+)/i', $text, $htags);

		$htags = fetch_or_get($htags[1], null);

		if (not_empty($htags)) {
			foreach ($htags as $key => $htag) {
				$htag      = cl_remove_emoji($htag);
				$htag_id   = 0;
				$db        = $db->where('tag',cl_text_secure($htag));
				$htag_data = $db->getOne(T_HTAGS,array('id','posts'));

				if (not_empty($htag_data)) {

					$htag_id = $htag_data['id'];

					$db->where('id', $htag_id)->update(T_HTAGS, array(
						'time'  => time(),
						'posts' => ($htag_data['posts'] += 1)
					));
				}
				else{
					$htag_id    =  $db->insert(T_HTAGS, array(
						'posts' => 1,
						'tag'   => $htag,
						'time'  => time(),
					));
				}

				$text = str_replace(cl_strf("#%s",$htag), cl_strf("{#id:%s#}",$htag_id), $text);
			}
		}
	}

	return $text;
}

function cl_update_htags($text = "") {
	global $db;

	if (not_empty($text)) {
		preg_match_all('/(\{\#id\:([0-9]+)\#\})/i', $text, $matches);

		$matches = fetch_or_get($matches[2], null);

		if (not_empty($matches)) {
			$db    = $db->where('id', $matches, "IN");		
			$htags = $db->get(T_HTAGS, null, array('id', 'posts'));

			if (not_empty($htags)) {
				foreach ($htags as $htag) {
					$num = ($htag['posts'] -= 1);
					$num = ((is_posnum($num)) ? $num : 0);

					if (empty($num)) {
						$db = $db->where('id', $htag['id']);
						$qr = $db->delete(T_HTAGS);
					}
					else {
						$db = $db->where('id', $htag['id']);
						$qr = $db->update(T_HTAGS, array('posts' => $num));
					}
				}
			}
		}
	}
}

function cl_tagify_htags($text = "") {
	global $db;

	if (not_empty($text)) {
		preg_match_all('/(\{\#id\:([0-9]+)\#\})/i', $text, $matches);

		$matches = fetch_or_get($matches[2], null);

		if (not_empty($matches)) {
			$db    = $db->where('id', $matches, "IN");		
			$htags = $db->get(T_HTAGS, null, array('id', 'tag'));

			if (not_empty($htags)) {
				foreach ($htags as $htag) {
					$text = str_replace(cl_strf("{#id:%d#}",$htag['id']), cl_strf("#%s",$htag['tag']), $text);
				}
			}
		}
	}

    return $text;
}

function cl_linkify_htags($text = "") {
    $text = preg_replace_callback('/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i', function($m) {

        $tag = fetch_or_get($m[1], "");

        if (not_empty($tag)) {
        	return cl_html_el('a', cl_strf("#%s", $tag), array(
	            'href'  => cl_link(cl_strf("search/posts?q=%s",cl_remove_emoji($tag))),
	            'class' => 'inline-link',
	        ));
        }

    }, $text);

    return $text;
}

function cl_get_hot_topics($limit = 8, $offset = false) {
    global $db, $cl, $me;

    $data = array();
    $db   = $db->where('posts', '0', '>');
    $db   = $db->orderBy('id','DESC');
    $db   = $db->orderBy('posts','DESC');
    $db   = $db->orderBy('time','DESC');
    $db   = (is_posnum($offset)) ? $db->where('id', $offset, '<') : $db;
    $tags = $db->get(T_HTAGS,$limit);

    if (cl_queryset($tags)) {
    	foreach ($tags as $tag_data) {
    		$tag_data['tag']     = cl_rn_strip($tag_data['tag']);
    		$tag_data['hashtag'] = cl_strf("#%s", $tag_data['tag']);
    		$tag_data['url']     = cl_link(cl_strf("search/posts?q=%s", cl_remove_emoji($tag_data['tag'])));
    		$tag_data['total']   = cl_number($tag_data['posts']);
    		$data[]              = $tag_data;
    	}
    }
    

    return $data;
}

function cl_get_htag_id($htag = "") {
	global $db;

	if (empty($htag)) {
		return false;
	}

	$htag_id      = 0;
	$db           = $db->where('tag', $htag);
	$query_result = $db->getOne(T_HTAGS, 'id');

	if (cl_queryset($query_result)) {
		$htag_id = $query_result['id'];
	}

	return $htag_id;
}

function cl_update_thread_replys($id = false, $count = "plus") {
	global $db, $cl;

	if (not_num($id)) {
		return 0;
	}

	$db           = $db->where('id', $id);
	$post         = $db->getOne(T_PUBS);
	$replys_count = 0;

	if (cl_queryset($post)) {
		$replys_count = (($count == "plus") ? ($post['replys_count'] += 1) : ($post['replys_count'] -= 1));
		$replys_count = ((is_posnum($replys_count)) ? $replys_count : 0);
		
		cl_update_post_data($id, array(
			'replys_count' => $replys_count
		));
	}

	return $replys_count;
}

function cl_post_data($post = array()) {
	global $cl;

	if (empty($post)) {
		return false;
	}

	$post_owner_data       = cl_user_data($post["user_id"]);
	$user_id               = ((empty($cl['is_logged'])) ? false : $cl['me']['id']);
	$post["time_raw"]      = $post["time"];
	$post["time"]          = cl_time2str($post["time"]);
	$post['text']          = stripcslashes($post['text']);
	$post['text']          = htmlspecialchars_decode($post['text'], ENT_QUOTES);
	$post["text"]          = cl_linkify_urls($post['text']);
    $post["text"]          = cl_tagify_htags($post['text']);
    $post["text"]          = cl_linkify_htags($post['text']);
    $post["text"]          = cl_likify_mentions($post['text']);
    $post["url"]           = cl_link(cl_strf("thread/%d",$post['id']));
    $post["replys_count"]  = cl_number($post["replys_count"]);
    $post["reposts_count"] = cl_number($post["reposts_count"]);
    $post["likes_count"]   = cl_number($post["likes_count"]);
    $post["can_delete"]    = false;
	$post["media"]         = array();
	$post["is_owner"]      = false;
	$post["has_liked"]     = false;
	$post["has_saved"]     = false;
	$post["has_reposted"]  = false;
	$post["reply_to"]      = array();
	$post["owner"]         = array(
		'id'               => $post_owner_data['id'],
		'url'              => $post_owner_data['url'],
		'avatar'           => $post_owner_data['avatar'],
		'username'         => $post_owner_data['username'],
		'name'             => $post_owner_data['name'],
		'verified'         => $post_owner_data['verified'],
	);

	if ($post["type"] != "text") {
		$post["media"] = cl_get_post_media($post["id"]);
	}

	if (not_empty($user_id) && ($post['user_id'] == $user_id)) {
		$post["is_owner"] = true;
	}

	if (not_empty($post["is_owner"]) || not_empty($cl["is_admin"])) {
		$post["can_delete"] = true;
	}

	if (not_empty($user_id)) {
		$post["has_liked"]    = cl_has_liked($user_id, $post["id"]);
		$post["has_saved"]    = cl_has_saved($user_id, $post["id"]);
		$post["has_reposted"] = cl_has_reposted($user_id, $post["id"]);
	}

	if (empty($post['offset_id'])) {
		$post["offset_id"] = $post['id'];
	}

	if (not_empty($post['thread_id'])) {
		$thread = cl_raw_post_data($post['thread_id']);

		if (not_empty($thread)) {

			$thread_owner = cl_user_data($thread['user_id']);

			if (not_empty($thread_owner)) {
				$post["reply_to"] = array(
					'id'          => $thread_owner['id'],
					'url'         => $thread_owner['url'],
					'avatar'      => $thread_owner['avatar'],
					'username'    => $thread_owner['username'],
					'name'        => $thread_owner['name'],
					'gender'      => $thread_owner['gender'],
					'is_owner'    => false,
					'thread_url'  => cl_link(cl_strf("thread/%d", $post['thread_id']))
				);

				if (not_empty($user_id) && ($post["reply_to"]["id"] == $user_id)) {
					$post["reply_to"]["is_owner"] = true;
				}
			}
		}
		else {
			cl_recursive_delete_post($post['id']);
		}
	}

	return $post;
}

function cl_raw_post_data($post_id = 0) {
    global $db;
    if (not_num($post_id)) {
        return false;
    } 

    $db        = $db->where('status', array('active','inactive','deleted'), 'IN');
    $db        = $db->where('id', $post_id);
    $post_data = $db->getOne(T_PUBS);

    if (empty($post_data)) {
        return false;
    }

    return $post_data;
}

function cl_recursive_delete_post($post_id = false) {
	global $db, $cl;

	if (not_num($post_id)) {
		return false;
	}

	$post_data = cl_raw_post_data($post_id);

	if (not_empty($post_data)) {
		$post_data['media']  = cl_get_post_media($post_id);
		$db                  = $db->where('thread_id', $post_id);
		$post_data['replys'] = $db->get(T_PUBS,null,array('id'));

		foreach ($post_data['media'] as $row) {
			if (in_array($row['type'], array('image','video'))) {
				cl_delete_media($row['src']);

				if (not_empty($row['x']['image_thumb'])) {
					cl_delete_media($row['x']['image_thumb']);
				}
				else if(not_empty($row['x']['poster_thumb'])) {
					cl_delete_media($row['x']['poster_thumb']);
				}
			}
		}

		$db = $db->where('id', $post_id);
		$rm = $db->delete(T_PUBS);

		$db = $db->where('pub_id', $post_id);
		$rm = $db->delete(T_PUBMEDIA);

		$db = $db->where('publication_id', $post_id);
		$rm = $db->delete(T_BOOKMARKS);

		$db = $db->where('pub_id', $post_id);
		$rm = $db->delete(T_LIKES);

		$db = $db->where('subject', array('like', 'repost', 'mention', 'reply'),'IN');
        $db = $db->where('entry_id', $post_id);
        $rm = $db->delete(T_NOTIFS);

        if (not_empty($post_data['text'])) {
        	cl_update_htags($post_data['text']);
        }
        
		if (not_empty($post_data['replys'])) {
			foreach ($post_data['replys'] as $row) {
				cl_recursive_delete_post($row['id']);
			}
		}
	}
}

function cl_has_liked($user_id = false, $post_id = false) {
	global $db, $cl;

	if (not_num($user_id) || not_num($post_id)) {
		return false;
	}

	$db = $db->where('user_id', $user_id);
	$db = $db->where('pub_id', $post_id);
	$qr = $db->getValue(T_LIKES, 'COUNT(*)');

	return (($qr > 0) ? true : false);
}

function cl_has_saved($user_id = false, $post_id = false) {
	global $db, $cl;

	if (not_num($user_id) || not_num($post_id)) {
		return false;
	}

	$db = $db->where('user_id', $user_id);
	$db = $db->where('publication_id', $post_id);
	$qr = $db->getValue(T_BOOKMARKS, 'COUNT(*)');

	return (($qr > 0) ? true : false);
}

function cl_has_reposted($user_id = false, $post_id = false) {
	global $db, $cl;

	if (not_num($user_id) || not_num($post_id)) {
		return false;
	}

	$db = $db->where('user_id', $user_id);
	$db = $db->where('publication_id', $post_id);
	$db = $db->where('type', 'repost');
	$qr = $db->getValue(T_POSTS, 'COUNT(*)');

	return (($qr > 0) ? true : false);
}

function cl_get_post_likes($post_id = false, $limit = 10, $offset = false) {
    global $db, $cl;

    if (is_posnum($post_id) != true) {
        return false;
    }

    $data         = array();
    $sql          = cl_sqltepmlate('components/sql/post/fetch_likes',array(
        't_users' => T_USERS,
        't_likes' => T_LIKES,
        'post_id' => $post_id,
        'limit'   => $limit,
        'offset'  => $offset,
    ));

    $query_result = $db->rawQuery($sql);

    if (cl_queryset($query_result)) {
        foreach ($query_result as $row) {
        	$row['about']        = cl_rn_strip($row['about']);
            $row['about']        = stripslashes($row['about']);
            $row['name']         = cl_strf("%s %s",$row['fname'],$row['lname']);      
            $row['avatar']       = cl_get_media($row['avatar']);
            $row['url']          = cl_link(cl_strf("@%s",$row['username']));
            $row['username']     = cl_strf("@%s",$row['username']);
            $row['last_active']  = date("d M, y h:m A",$row['last_active']);
            $row['is_following'] = false;
            $row['is_user']      = false;

            if (not_empty($cl['is_logged'])) {
                $row['is_following'] = cl_is_following($cl['me']['id'], $row['id']);

                if ($cl['me']['id'] == $row['id']) {
                    $row['is_user'] = true; 
                }
            }

            $data[] = $row;
        }
    }

    return $data;
}