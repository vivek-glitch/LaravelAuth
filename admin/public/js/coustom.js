
// for opening and colsing the search tab


$(document).ready(function(){
    $("#serchOpenbtn").click(function(){
        $("#searchDiv").slideDown(500);    

    });
    $(".searchDivClose").click(function(){
        $("#searchDiv").slideUp(500);
    })
});
