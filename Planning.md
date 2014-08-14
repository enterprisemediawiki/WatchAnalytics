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