(doc) ->
  emit doc.uuid, null if (doc.question isnt "Attendance List" and doc.question isnt "MARP-es" and doc.question isnt "Exit Survey-es")