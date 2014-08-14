Planning
========

Use the following to work with extension:wiretap

```sql
SELECT 
	p.page_id AS page_id
	p.page_title AS page_title,
	p.page_namespace AS page_namespace,
	COUNT(DISTINCT(user_name)) AS unique_hits,
	red.rd_namespace AS redir_to_ns,
	red.rd_title AS redir_to_title,
	redir_page.page_id AS redir_id
FROM wiretap AS w
INNER JOIN page AS p ON 
	p.page_id = w.page_id
LEFT JOIN redirect AS red ON
	red.rd_from = p.page_id
LEFT JOIN page AS redir_page ON
	red.rd_namespace = redir_page.page_namespace
	AND red.rd_title = redir_page.page_title
WHERE
	hit_timestamp > 20140801000000
GROUP BY
	p.page_namespace, p.page_title
ORDER BY
	unique_hits DESC
LIMIT 20;
```

Use something like this to count watches and such:

```sql
SELECT * FROM (
	SELECT
		p.page_title,
		p.page_namespace,
		p.page_counter,
		p.page_len,
		SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS num_watches,
		p.page_counter / SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS view_watch_ratio,
		p.page_len / SUM( IF(w.wl_title IS NOT NULL, 1, 0) ) AS length_watch_ratio
	FROM
		watchlist AS w
	LEFT JOIN page AS p ON
		w.wl_title = p.page_title
		AND w.wl_namespace = p.page_namespace
	LEFT JOIN categorylinks AS c ON
		c.cl_from = p.page_id
	LEFT JOIN user AS u ON
		u.user_id = w.wl_user
	LEFT JOIN revision AS r ON
		r.rev_id = p.page_latest
	WHERE
		p.page_namespace = 0
		AND p.page_is_redirect = 0
		AND r.rev_timestamp > 20140701000000
	GROUP BY
		p.page_title, p.page_namespace
	ORDER BY
		view_watch_ratio DESC
) AS tmp WHERE num_watches < 2 LIMIT 10;
```

Or combine them like this:

```sql
SELECT 
	p.page_id AS page_id,
	p.page_title AS page_title,
	p.page_namespace AS page_namespace,
	COUNT(DISTINCT(user_name)) AS unique_hits,
	red.rd_namespace AS redir_to_ns,
	red.rd_title AS redir_to_title,
	redir_page.page_id AS redir_id,
	(
		SELECT COUNT(*)
		FROM watchlist AS watch
		WHERE
			watch.wl_namespace = p.page_namespace
			AND watch.wl_title = p.page_title
	) AS watches
FROM wiretap AS w
INNER JOIN page AS p ON 
	p.page_id = w.page_id
LEFT JOIN redirect AS red ON
	red.rd_from = p.page_id
LEFT JOIN page AS redir_page ON
	red.rd_namespace = redir_page.page_namespace
	AND red.rd_title = redir_page.page_title
WHERE
	hit_timestamp > 20140801000000
GROUP BY
	p.page_namespace, p.page_title
ORDER BY
	unique_hits DESC
LIMIT 20;
```