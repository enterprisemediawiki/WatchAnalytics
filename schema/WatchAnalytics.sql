
CREATE TABLE /*_*/watch_tracking_user (
	tracking_timestamp  VARBINARY(14) NOT NULL,
	user_id             INT(10) NOT NULL,
	num_watches         INT(10) NOT NULL,
	num_pending         INT(10) NOT NULL,
	event_notes			VARCHAR(63)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/watch_tracking_user_datapoint ON /*_*/watch_tracking_user (tracking_timestamp,user_id);
CREATE INDEX /*i*/watch_tracking_user_user      ON /*_*/watch_tracking_user (user_id);
CREATE INDEX /*i*/watch_tracking_user_timestamp ON /*_*/watch_tracking_user (tracking_timestamp);


CREATE TABLE /*_*/watch_tracking_page (
	tracking_timestamp  VARBINARY(14) NOT NULL,
	page_id             INT(10) NOT NULL,
	num_watches         INT(10) NOT NULL,
	num_reviewed        INT(10) NOT NULL,
	event_notes			VARCHAR(63)
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/watch_tracking_page_datapoint ON /*_*/watch_tracking_page (tracking_timestamp,page_id);
CREATE INDEX        /*i*/watch_tracking_page_page      ON /*_*/watch_tracking_page (page_id);
CREATE INDEX        /*i*/watch_tracking_page_timestamp ON /*_*/watch_tracking_page (tracking_timestamp);


CREATE TABLE /*_*/watch_tracking_wiki (
	tracking_timestamp          VARBINARY(14) NOT NULL,
	
	num_pages                   INT(10) NOT NULL,
	num_watches                 INT(10) NOT NULL,
	num_pending                 INT(10) NOT NULL,
	max_pending_minutes         INT(10) NOT NULL,
	avg_pending_minutes         INT(10) NOT NULL,

	num_unwatched               INT(10) NOT NULL,
	num_one_watched             INT(10) NOT NULL,
	num_unreviewed              INT(10) NOT NULL,
	num_one_reviewed            INT(10) NOT NULL,

	content_num_pages           INT(10) NOT NULL,
	content_num_watches         INT(10) NOT NULL,
	content_num_pending	        INT(10) NOT NULL,
	content_max_pending_minutes INT(10) NOT NULL,
	content_avg_pending_minutes INT(10) NOT NULL,

	content_num_unwatched       INT(10) NOT NULL,
	content_num_one_watched     INT(10) NOT NULL,
	content_num_unreviewed      INT(10) NOT NULL,
	content_num_one_reviewed    INT(10) NOT NULL,

	event_notes			        VARCHAR(63)
) /*$wgDBTableOptions*/;


CREATE UNIQUE INDEX /*i*/watch_tracking_wiki_datapoint ON /*_*/watch_tracking_wiki (tracking_timestamp);
