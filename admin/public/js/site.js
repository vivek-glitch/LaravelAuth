/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
// PROXY_URL = SITE_URL+'/ajax/';
function checkAll()
{   
    $('.chkItem').prop('checked', 'checked');
     return false;
}

function viewAlert(msg, ctrlId,redLoc)
{	
    $('#btnAlertOk').off('click');
    if(typeof(ctrlId)=='undefined')
    {
            ctrlId	= '';
    }
    if(typeof(redLoc)=='undefined')
    {
            redLoc	= '';
    }
    $('#alertModal').modal({backdrop: 'static', keyboard: false});
    $('.alertMessage').html(msg);
    $('#btnAlertOk').on('click',function(){
            $('#alertModal').modal('hide');
            $('#alertModal').hide();
            if(ctrlId !='')
            {
                    $('#'+ctrlId).focus(); //addClass('vfail').
            }
            if(redLoc!='')
            {
                    window.location.href =redLoc;
            }
            if(redLoc=='pr'){ //page reload
                window.location.reload();
            }
    });

}


function confirmAlert(msg)
{
    $('#confirmModal').modal({backdrop: 'static', keyboard: false});
    $('.confirmMessage').html(msg);
}

function confirmLogoutAlert(msg)
{
    $('#confirmLogoutModal').modal({backdrop: 'static', keyboard: false});
    $('.confirmMessage').html(msg);
}

function updateConfirmAlert(msg)
{
    $('#updateModal').modal({backdrop: 'static', keyboard: false})
    .one('click', '#btnUpdtYes', function(e) {
      return true;
    });
    $('.confirmMessage').html(msg);


} 
    
        //Confirm Alert for Delete single Row wise record only By Ashok Kumar Samal :: ON : 16-NOV-2016

 function confirmDelete(strMsg,hdnControl,intRecordId)
{
    $('#'+hdnControl).val(intRecordId);
    $('#confirmDeleteModal').modal({backdrop: 'static', keyboard: false});
    $('.confirmDeleteMessage').html(strMsg);
}


function DoPaging(CurrentPage,RecordNo)
{
    $("#hdn_PageNo").val(CurrentPage);
    $("#hdn_RecNo").val(RecordNo);

    $("#listForm").submit();
}

function AlternatePaging()
{
    if($('#hdn_IsPaging').val()=="0")	
            $("#hdn_IsPaging").val("1");
    else	
    $("#hdn_IsPaging").val("0");

    $("#listForm").submit();	
}

/*
 Function to get Page.
 By: Madhulita Sahoo
 On: 21-Feb-2018
 */
function getPublishedPage(rbtShowType,intDeptId)
{
    $.ajax({
        url: PROXY_URL + 'getPublishedPage',
        method: 'get',        
        dataType: "json",
        data: {rbtShowType:rbtShowType,intDeptId:intDeptId,_token: csrftoken},
        success: function (data) {
            var res = data.result;
            var finalRes = res.split('~::~');
            /* Load results. */
            $("#pageListDiv").html(finalRes[0]);
            $("#hdnFldForPageId").val(finalRes[1]);
        }
    });
}
 /*
 Function to delete menu list.
 By: Madhulita Sahoo
 On: 21-Feb-2018
 */
function deleteMenu(id,menuId,menuType)
{
    $('#confirmModal').modal({backdrop: 'static', keyboard: false});
        $('.confirmMessage').html('Proceed to remove the menu');
        $('#btnConfirmOk').on('click',function(){
        $.ajax({
            type: "post",
            url: PROXY_URL + 'deleteMenu',
            dataType: "json",
            data: {PID:menuId,_token: csrftoken},
            success: function (data) {
                if(menuType==1){
                    $("#liId" + id).remove();
                    showHideChkBox(1);
                    displayEmptyText(1);
                }
                if(menuType==3){
                    $("#liIdb" + id).remove();
                    showHideChkBox(3);
                    displayEmptyText(3);
                    
                }
                if(menuType==5){
                   $("#liIdbt" + id).remove();
                    showHideChkBox(5);
                    displayEmptyText(5);
                }
                if(menuType==4){
                   $("#liIdbp" + id).remove();
                    showHideChkBox(4);
                    displayEmptyText(4);
                }
                if(menuType==6){
                   $("#liIdMid" + id).remove();
                    showHideChkBox(6);
                    displayEmptyText(6);
                }
                if(menuType==7){
                   $("#deptMenuItem" + id).remove();
                    showHideChkBox(6);
                    displayEmptyText(6);
                }
                //getTotalMenuRecords();
            }
        });
    });
    
}
/*
 Function to get all records.
  By: Madhulita Sahoo
 On: 21-Feb-2018
 */
function getTotalMenuRecords()
{
    $.ajax({
        type: "post",
        url: PROXY_URL + 'getTotalMenuRecords',
        dataType: "json",
        data: {_token: csrftoken},
        success: function (data)
        {
            var res = data.result;

            /* Load results. */
            $("#hdnTotalMenuRecords").val(res);
        }
    });
}
/*
 Function to delete main menu.
 By: Madhulita Sahoo
 On: 22-Feb-2018
 */
function deleteFromMainMenu(menuId, pageId,menuType)
{
    
    if (!confirm('Proceed to remove the menu'))
        return false;
    /*$.ajax({
        type: "POST",
        url: appURL + '/proxy',
        dataType: "json",
        data: {method: 'deleteFromMainMenu', menuId: menuId, pageId: pageId},
        success: function (data) {
            var res = data.result;
            if (res == 1)
            {
                alert('Can not delete this global link as primary links present under this menu.');
            }
            else if (res == 2)
            {*/
                $("#mainMenuItem" + pageId).remove();
                showHideChkBox(menuType);
                displayEmptyText(menuType);
                //getTotalMenuRecords();
//            }
//        }
//    });
}

/*
 Function to fill menu list
  By: Madhulita Sahoo
 On: 22-Feb-2018
 */
function fillMainMenuList(fillCtrlId, menutype,rbtShowType,intDeptId)
{
    $.ajax({
        type: "post",
        dataType: "json",
        url: PROXY_URL + 'fillMainMenuList',
        data: {menutype:menutype,rbtShowType:rbtShowType,intDeptId:intDeptId, _token: csrftoken},
        success: function (data)
        { 
            var tabdiv = '';
            var res = data.menu;
            if (res != '') {
                $(res).each(function (i) {
                    tabdiv += res[i];
                });
                //alert(tabdiv);
                $("#" + fillCtrlId).html(tabdiv);
                $('.dd').nestable();
                $('.dd-handle a').on('click', function (e) {
                    e.stopPropagation();

                });
                $('.dd-handle a').on('mousedown', function (e) {

                    e.stopPropagation();
                });

                $('.dd-handle a').on('mousedown', function (e) {
                    e.stopPropagation();
                });
                $('.dd-handle a').on('click', function (e) {
                    e.stopPropagation();
                });
                $('.dd-handle .txt').on('mousedown', function (e) {
                    e.stopPropagation();
                });
            }
            else
            {

                // tabdiv += '<ol class="dd-list" id="nestable"> <span id="mainMenu"> </span></ol> ';
                displayEmptyText(menutype);
                showHideChkBox(menutype);
                displayEmptyTextOpt(menutype);
                //alert(menutype);
            }
        }
    });

}


function PrintPage() {
    $('.allowPrint').css('display', 'block');
    $('[data-toggle="tooltip"]').tooltip('hide');
    var windowName = "PrintPage";
    var wOption    = "width=1000,height=600,menubar=yes,scrollbars=yes,location=no,left=100,top=100";
    // var cloneTable   = $(".table").parent().clone();
    if($('div.topScrollWrapper').length > 0) {
        $('.topScrollWrapper').addClass('noPrint');
    }
    if($('div.card-body').length == 1) {
        var cloneTable     = $("div.card-body").clone();    
    } else {
        var cloneTable     = $("div.printTable").clone();    
    }
    cloneTable.find('input[type=text],select,textarea').not('.noPrint').each(function(){
        var elementType = $(this).prop('tagName');  
        if(elementType=='SELECT')
            var textVal = $(this).find("option:selected").text();
        else
            var textVal = $(this).val();
                        
        $(this).replaceWith('<label>'+ textVal +'</label>');
    });
    cloneTable.find('a').each(function(){
        var anchorVal   = $(this).html();
        $(this).replaceWith('<span>'+anchorVal+'</span>');
    });
        var pageTitle   = $(".page-title .title-details h4").text();
          //<h4>State Scholarship Portal <small>Govt. Of Odisha</small></h4> 
        var wWinPrint   = window.open("",windowName,wOption);
        wWinPrint.document.open();
        wWinPrint.document.write("<html><head><link href='"+PUB_URL+"/css/bootstrap.min.css' rel='stylesheet'><title>"+pageTitle+"</title>");
        wWinPrint.document.write("<link href='"+PUB_URL+"/css/print.css' rel='stylesheet'>");
        wWinPrint.document.write("</head><body>");
        wWinPrint.document.write("<div id='header'><img src='"+PUB_URL+"/images/odishalogo.png' style='float:left; margin-right: 5px;' border='0' align='absmiddle' alt='' class='logo' height='85px;' /><div class='pull-left text_logo'><h5>State Scholarship Portal</h5><h6>Govt. of Odisha</h6></div><a href='javascript:void(0)' title='Print' class='btn btn-success btn-sm pull-right' style='margin-right:10px; margin-top:20px; background-color: #5cb85c;border-width: 1px;font-size: 13px; padding: 4px 9px;color:#fff;text-decoration: none;float: right;border-color: #4cae4c' onclick='$(this).hide();window.print();$(this).show();'><i class='icon-white icon-print'></i> Print</a><div class='clear'>&nbsp;</div></div>")
        wWinPrint.document.write("<div id='printHeader' style='margin-top: 2px;'><h5>" + pageTitle + "</h5></div>");
        wWinPrint.document.write("<div id='printContent'>"+cloneTable.html()+"</div>");
        wWinPrint.document.write("<div id='printFooter'>&copy;"+ getCurrentFiscalYear()+ ",All right reserved</div>");
        wWinPrint.document.write("<script src='"+PUB_URL+"/js/jquery-3.3.1.min.js' type='text/javascript'></script></body></html>");
        wWinPrint.document.close();
        wWinPrint.focus();
        //wWinPrint.print();
        $('.allowPrint').css('display', 'none');
        return wWinPrint;
}

function getCurrentFiscalYear() {

    //get current date
    var today = new Date();
    
    //get current month
    var curMonth = today.getMonth();
    
    var fiscalYr = "";
    if (curMonth > 3) { //
        var nextYr1 = (today.getFullYear() + 1).toString();
        fiscalYr = today.getFullYear().toString() + "-" + nextYr1.charAt(2) + nextYr1.charAt(3);
    } else {
        var nextYr2 = today.getFullYear().toString();
        fiscalYr = (today.getFullYear() - 1).toString() + "-" + nextYr2.charAt(2) + nextYr2.charAt(3);
    }
    
    //document.write(fiscalYr);
    return fiscalYr;
 }