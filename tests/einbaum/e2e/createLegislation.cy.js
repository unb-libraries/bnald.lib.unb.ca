describe.only('Create a "Piece of Legislation" entity', () => {
  Cypress.Workflows.run('createEntity', {
    entityType: 'legislation',
    users: {
      authorized: [
        'bnald_editor',
      ],
      unauthorized: [
        'user',
      ],
    },
    formUrl: '/legislation/add',
    formData: {
      "autocomplete:origin": "Acts of the General Assembly of Her Majesty's Province of New Brunswick passed in the year 1844. Fredericton: John Simpson, 1844",
      "text:title": "An Act in addition to an Act, intituled An Act to prevent Nuisances within the City of Saint John. Passed 25th March 1844.",
      chapter: "7 Victoria Chapter 22",
      year: 1844,
      article_count: 5,
      "autocomplete:province": "New Brunswick",
      "text:summary": "This act creates new regulations for public health and safety in Saint John and penalties for the violation.",
      "text:full_text": "Whereas buildings have been erected in the City of Saint John, covering the whole ground belonging to the owner thereof, without privies...",
      "fileselect:pdf_original": "original.pdf",
      "fileselect:pdf_transcribed": "transcribed.pdf",
      "autocomplete:jurisdictional_relevance": [
        "Local",
      ],
      "autocomplete:concepts": [
        "Public Health",
        "Public Order",
      ],
      "text:notes": "This is a test entry.",
    },
    successMessage: "Created the An Act in addition to an Act, intituled An Act to prevent Nuisances within the City of Saint John. Passed 25th March 1844. Legislation.",
    successUrl: "/legislation/act-addition-act-intituled-act-prevent-nuisances-within-city-saint-john-passed-25th(-[0-9]+)?",
  })

})
