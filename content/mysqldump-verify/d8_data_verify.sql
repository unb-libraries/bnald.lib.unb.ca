SELECT
  legislation.title,
  legislation.summary,
  legislation.full_text,
  pdf_o.uri as pdf_original,
  pdf_t.uri as pdf_transcript,
  legislation.article_count as articles,
  legislation.notes,
  legislation.chapter,
  legislation.chapter_sort,
  legislation.year,
  province.name as province,
  concepts.concepts as concepts,
  jr.jurisdictional_relevance as jurisdictional_relevance,
  source_document.title as src_title,
  source_document.year as src_year,
  printer.name as src_printer,
  location.name as src_location,
  legislation.status as published,
  authors.name as creator
FROM legislation
LEFT JOIN (
  SELECT tid, name
  FROM taxonomy_term_field_data
  WHERE vid = 'provinces'
) AS province ON province.tid = legislation.province
LEFT JOIN(
  SELECT LC.entity_id, GROUP_CONCAT(concept_terms.name ORDER BY concept_terms.name SEPARATOR'|') as concepts
  FROM
  (
    SELECT tid, name
    FROM taxonomy_term_field_data as TTD1
    WHERE vid = 'concepts'
  ) AS concept_terms
  LEFT JOIN legislation__concepts AS LC ON LC.concepts_target_id = concept_terms.tid
  GROUP BY LC.entity_id
) as concepts ON concepts.entity_id = legislation.id
LEFT JOIN (
  SELECT LJR.entity_id, GROUP_CONCAT(jr_terms.name ORDER BY jr_terms.name SEPARATOR'|') as jurisdictional_relevance
  FROM
  (
    SELECT tid, name
    FROM taxonomy_term_field_data as TTD1
    WHERE vid = 'jurisdictional_relevance'
  ) AS jr_terms
  LEFT JOIN legislation__jurisdictional_relevance AS LJR ON LJR.jurisdictional_relevance_target_id = jr_terms.tid
  GROUP BY LJR.entity_id
) as jr ON jr.entity_id = legislation.id
LEFT JOIN source_document ON source_document.id = legislation.origin
LEFT JOIN (
  SELECT tid, name
  FROM taxonomy_term_field_data
  WHERE vid = 'printers'
) AS printer ON printer.tid = source_document.printer
LEFT JOIN (
  SELECT tid, name
  FROM taxonomy_term_field_data
  WHERE vid = 'print_locations'
) AS location ON location.tid = source_document.print_location
LEFT JOIN (
  SELECT U.uid, UFD.name
  FROM users as U
  LEFT JOIN users_field_data as UFD ON U.uid = UFD.uid
) as authors ON authors.uid = legislation.user_id
LEFT JOIN (
  SELECT fid, uri
  FROM file_managed
  WHERE filemime = 'application/pdf'
) as pdf_o ON pdf_o.fid = legislation.pdf_original__target_id
LEFT JOIN (
  SELECT fid, uri
  FROM file_managed
  WHERE filemime = 'application/pdf'
) as pdf_t ON pdf_t.fid = legislation.pdf_transcribed__target_id
INTO OUTFILE '/var/lib/mysql-files/bnald_d8.csv'
FIELDS TERMINATED BY ';'
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
