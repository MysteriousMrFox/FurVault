EXPLAIN SELECT DISTINCT
	itm.id as itemId
FROM
	ItemTagMappings mapping
INNER JOIN
	Items itm ON mapping.itemId = itm.id
INNER JOIN
	ItemTagMappings mapping2 ON mapping2.itemId = itm.id
WHERE
	mapping.tagId IN (4, 27) -- IDs of tags to include
GROUP BY
	mapping2.tagId, itm.id
HAVING
	COUNT(mapping.tagId) = 2 -- How many IDs are IN the list above
ORDER BY
	itm.id