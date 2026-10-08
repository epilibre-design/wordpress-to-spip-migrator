-- Tables WordPress lues par wp2spip, en SQLite ; {prefixe} est remplacé par le préfixe des tables
CREATE TABLE {prefixe}posts (
	ID INTEGER PRIMARY KEY,
	post_author INTEGER NOT NULL DEFAULT 0,
	post_date TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	post_date_gmt TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	post_content TEXT NOT NULL DEFAULT '',
	post_title TEXT NOT NULL DEFAULT '',
	post_excerpt TEXT NOT NULL DEFAULT '',
	post_status TEXT NOT NULL DEFAULT 'publish',
	comment_status TEXT NOT NULL DEFAULT 'open',
	ping_status TEXT NOT NULL DEFAULT 'open',
	post_password TEXT NOT NULL DEFAULT '',
	post_name TEXT NOT NULL DEFAULT '',
	to_ping TEXT NOT NULL DEFAULT '',
	pinged TEXT NOT NULL DEFAULT '',
	post_modified TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	post_modified_gmt TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	post_content_filtered TEXT NOT NULL DEFAULT '',
	post_parent INTEGER NOT NULL DEFAULT 0,
	guid TEXT NOT NULL DEFAULT '',
	menu_order INTEGER NOT NULL DEFAULT 0,
	post_type TEXT NOT NULL DEFAULT 'post',
	post_mime_type TEXT NOT NULL DEFAULT '',
	comment_count INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE {prefixe}postmeta (
	meta_id INTEGER PRIMARY KEY,
	post_id INTEGER NOT NULL DEFAULT 0,
	meta_key TEXT DEFAULT NULL,
	meta_value TEXT
);
CREATE TABLE {prefixe}comments (
	comment_ID INTEGER PRIMARY KEY,
	comment_post_ID INTEGER NOT NULL DEFAULT 0,
	comment_author TEXT NOT NULL DEFAULT '',
	comment_author_email TEXT NOT NULL DEFAULT '',
	comment_author_url TEXT NOT NULL DEFAULT '',
	comment_author_IP TEXT NOT NULL DEFAULT '',
	comment_date TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	comment_date_gmt TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	comment_content TEXT NOT NULL DEFAULT '',
	comment_karma INTEGER NOT NULL DEFAULT 0,
	comment_approved TEXT NOT NULL DEFAULT '1',
	comment_agent TEXT NOT NULL DEFAULT '',
	comment_type TEXT NOT NULL DEFAULT 'comment',
	comment_parent INTEGER NOT NULL DEFAULT 0,
	user_id INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE {prefixe}terms (
	term_id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT '',
	slug TEXT NOT NULL DEFAULT '',
	term_group INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE {prefixe}term_taxonomy (
	term_taxonomy_id INTEGER PRIMARY KEY,
	term_id INTEGER NOT NULL DEFAULT 0,
	taxonomy TEXT NOT NULL DEFAULT '',
	description TEXT NOT NULL DEFAULT '',
	parent INTEGER NOT NULL DEFAULT 0,
	count INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE {prefixe}term_relationships (
	object_id INTEGER NOT NULL DEFAULT 0,
	term_taxonomy_id INTEGER NOT NULL DEFAULT 0,
	term_order INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY (object_id, term_taxonomy_id)
);
CREATE TABLE {prefixe}options (
	option_id INTEGER PRIMARY KEY,
	option_name TEXT NOT NULL DEFAULT '' UNIQUE,
	option_value TEXT NOT NULL DEFAULT '',
	autoload TEXT NOT NULL DEFAULT 'yes'
);
CREATE TABLE {prefixe}users (
	ID INTEGER PRIMARY KEY,
	user_login TEXT NOT NULL DEFAULT '',
	user_pass TEXT NOT NULL DEFAULT '',
	user_nicename TEXT NOT NULL DEFAULT '',
	user_email TEXT NOT NULL DEFAULT '',
	user_url TEXT NOT NULL DEFAULT '',
	user_registered TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	user_activation_key TEXT NOT NULL DEFAULT '',
	user_status INTEGER NOT NULL DEFAULT 0,
	display_name TEXT NOT NULL DEFAULT ''
);
CREATE TABLE {prefixe}usermeta (
	umeta_id INTEGER PRIMARY KEY,
	user_id INTEGER NOT NULL DEFAULT 0,
	meta_key TEXT DEFAULT NULL,
	meta_value TEXT
);
