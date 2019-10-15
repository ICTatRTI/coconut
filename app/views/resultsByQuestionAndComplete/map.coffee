(document) ->
  if document.collection is "result"  and (document.Estecolateralparticipante is undefined or (document.Estecolateralparticipante isnt "Sí" and document.Estecolateralparticipante isnt "Indirecto"))
    if document.Completado is "true"
      emit document.question + ':true:' + document.lastModifiedAt, null
    else
      emit document.question + ':false:' + document.lastModifiedAt, null




