<div class="modal fade in" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" >
    <div class="modal-dialog">
        <div class="modal-content text-center">
            <div class="modal-body no-padding">
                <h4 class="alertMessage"></h4>
                <div class="form-group">
                    <div class="center"> <a class=" btn btn-danger btn-sm text-light " id="btnAlertOk" style="width:100px; margin-top:30px;margin-left:400px">Ok</a> </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade in" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" >
    <div class="modal-dialog">
        <div class="modal-content text-center">
            <div class="modal-body no-padding">
                <h4 class="confirmMessage center"></h4>
                <div class="form-group">
                    <div class="center">
                        <input type="hidden" id="confirmModalHref">
                        <a class=" btn btn-primary btn-sm" id="btnConfirmOk" data-dismiss="modal" style="width:100px; margin-top:30px;">Yes</a> <a class=" btn btn-danger btn-sm" id="btnConfirmCancel" data-dismiss="modal" style="width:100px; margin-top:30px;">No</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="confirmLogoutModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" >
    <div class="modal-dialog">
        <div class="modal-content text-center">
            <div class="modal-body no-padding">
                <h5 class="confirmMessage center"></h5>
                <div class="form-group">
                    <div class="center"> <a class=" btn btn-primary btn-sm" style="width:100px; margin-top:30px;" href="{{ url('logout') }}">Logout</a> <a class=" btn btn-danger btn-sm" id="btnConfirmCancel" data-dismiss="modal" style="width:100px; margin-top:30px;color:#fff;">Cancel</a> </div>
                </div>
            </div>
        </div>
    </div>
</div>
