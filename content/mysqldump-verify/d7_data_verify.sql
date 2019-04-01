SELECT
  title.field_legislation_title_value as title,
  summary.field_legislative_summary_value as summary,
  full_text.field_full_legislative_text_value as full_text,
  pdf_original.uri as pdf_original,
  pdf_transcript.uri as pdf_transcript,
  articles.field_number_of_articles_value as articles,
  notes.field_item_notes_value as notes,
  chapter.field_chapter_value as chapter,
  chapter_sort.field_chapter_sort_value as chapter_sort,
  year(date_passed.field_date_legislation_passed_value) as year,
  province.name as province,
  concepts.names AS concepts,
  jurisdictional_relevance.names AS jurisdictional_relevance,
  source_document.title as src_title,
  year(source_document.date_printed) as src_year,
  source_document.printer as src_printer,
  source_document.location as src_location,
  legislation.status as published,
  users.name as creator
FROM (
  SELECT nid as id, uid as owner, status
  FROM node
  WHERE type = 'piece_of_legislation'
) as legislation
LEFT JOIN field_data_field_legislation_title as title ON title.entity_id = legislation.id
LEFT JOIN field_data_field_legislative_summary as summary ON summary.entity_id = legislation.id
LEFT JOIN field_data_field_full_legislative_text as full_text ON full_text.entity_id = legislation.id
LEFT JOIN (
  SELECT pdf_original_value.entity_id, fm1.fid, fm1.uri
  FROM (
    SELECT fid, uri
    FROM file_managed
    WHERE filemime = 'application/pdf'
  ) as fm1
  LEFT JOIN field_data_field_pdf_of_legislation as pdf_original_value ON pdf_original_value.field_pdf_of_legislation_fid = fm1.fid
) as pdf_original ON pdf_original.entity_id = legislation.id
LEFT JOIN (
  SELECT pdf_transcript_value.entity_id, fm2.fid, fm2.uri
  FROM (
    SELECT fid, uri
    FROM file_managed
    WHERE filemime = 'application/pdf'
  ) as fm2
  LEFT JOIN field_data_field_pdf_of_legislation_full_te as pdf_transcript_value ON pdf_transcript_value.field_pdf_of_legislation_full_te_fid = fm2.fid
) as pdf_transcript ON pdf_transcript.entity_id = legislation.id
LEFT JOIN field_data_field_number_of_articles as articles ON articles.entity_id = legislation.id
LEFT JOIN field_data_field_item_notes as notes ON notes.entity_id = legislation.id
LEFT JOIN field_data_field_chapter as chapter ON chapter.entity_id = legislation.id
LEFT JOIN field_data_field_chapter_sort as chapter_sort ON chapter_sort.entity_id = legislation.id
LEFT JOIN field_data_field_date_legislation_passed as date_passed ON date_passed.entity_id = legislation.id
LEFT JOIN (
  SELECT p_field_data.entity_id, province_terms.name as name
  FROM (
    SELECT TTD3.tid, TTD3.name
    FROM taxonomy_vocabulary as TV3, taxonomy_term_data as TTD3
    WHERE machine_name ='province' AND TV3.vid = TTD3.vid
  ) as province_terms
  LEFT JOIN field_data_field_provinces as p_field_data ON p_field_data.field_provinces_tid = province_terms.tid
) as province ON province.entity_id = legislation.id
LEFT JOIN (
  SELECT c_field_data.entity_id, GROUP_CONCAT(concepts_terms.name ORDER BY concepts_terms.name ASC SEPARATOR '|') as names
  FROM (
    SELECT TTD1.tid, TTD1.name
    FROM taxonomy_vocabulary as TV1, taxonomy_term_data as TTD1
    WHERE machine_name ='concepts' AND TV1.vid = TTD1.vid
  ) as concepts_terms
  LEFT JOIN field_data_field_concepts as c_field_data ON c_field_data.field_concepts_tid = concepts_terms.tid
  GROUP BY c_field_data.entity_id
) as concepts ON concepts.entity_id = legislation.id
LEFT JOIN (
  SELECT jr_field_data.entity_id, GROUP_CONCAT(jr_terms.name ORDER BY jr_terms.name ASC SEPARATOR '|') as names
  FROM (
    SELECT TTD2.tid, TTD2.name
    FROM taxonomy_vocabulary as TV2, taxonomy_term_data as TTD2
    WHERE machine_name ='jurisdictional_relevance' AND TV2.vid = TTD2.vid
  ) as jr_terms
  LEFT JOIN field_data_field_jurisdictional_relevance as jr_field_data ON jr_field_data.field_jurisdictional_relevance_tid = jr_terms.tid
  GROUP BY jr_field_data.entity_id
) as jurisdictional_relevance ON jurisdictional_relevance.entity_id = legislation.id
LEFT JOIN users ON users.uid = legislation.owner
LEFT JOIN (
  SELECT sd.entity_id, sd.tid, sd.name as title, date_printed.field_date_printed_value as date_printed, printers.name as printer, print_locations.name as location
  FROM (
    SELECT sd_field_data.entity_id, sd_terms.tid, sd_terms.name
    FROM (
      SELECT TTD4.tid, TTD4.name
      FROM taxonomy_vocabulary as TV4, taxonomy_term_data as TTD4
      WHERE machine_name = 'source_documents' AND TV4.vid = TTD4.vid
    ) as sd_terms
    LEFT JOIN field_data_field_source_document as sd_field_data ON sd_field_data.field_source_document_tid = sd_terms.tid
  ) as sd
  LEFT JOIN field_data_field_date_printed as date_printed ON date_printed.entity_id = sd.tid
  LEFT JOIN (
    SELECT pn_field_data.entity_id, pn_terms.name as name
    FROM (
      SELECT TTD5.tid, TTD5.name
      FROM taxonomy_vocabulary as TV5, taxonomy_term_data as TTD5
      WHERE machine_name = 'printer_name' AND TV5.vid = TTD5.vid
    ) as pn_terms
    LEFT JOIN field_data_field_printer as pn_field_data ON pn_field_data.field_printer_tid = pn_terms.tid
  ) as printers ON printers.entity_id = sd.tid
  LEFT JOIN (
    SELECT pl_field_data.entity_id, pl_terms.name as name
    FROM (
      SELECT TTD6.tid, TTD6.name
      FROM taxonomy_vocabulary as TV6, taxonomy_term_data as TTD6
      WHERE machine_name = 'regions' AND TV6.vid = TTD6.vid
    ) as pl_terms
    LEFT JOIN field_data_field_location_printed as pl_field_data ON pl_field_data.field_location_printed_tid = pl_terms.tid
  ) as print_locations ON print_locations.entity_id = sd.tid
) as source_document ON source_document.entity_id = legislation.id
INTO OUTFILE '/var/lib/mysql-files/bnald_d7.csv'
FIELDS TERMINATED BY ';'
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
