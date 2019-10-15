/**
 * Created by vbakalov on 9/4/2014.
 */
function(doc) {
    if (doc.question === "Participant Registration-es" && (doc.Estecolateralparticipante === "Sí" || doc.Estecolateralparticipante === "Indirecto") && doc.Nombre === "Raquel") {
        return emit(doc._id, null);
    }
}
