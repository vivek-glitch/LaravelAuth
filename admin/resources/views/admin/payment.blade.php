@include('layouts.main')
<script>
    var openFlag = '<?php echo $openFlag; ?>';

if(openFlag == 'S')
{
$(document).ready(function(){
    $("#searchDiv").show();

});

}
</script>
<body>
    @include('layouts.sidebar')

    <div class="main-content" id="panel">
        <!-- Topnav -->
        @include('layouts.topbar')
        @include('layouts.viewAlert')
        <!-- Header -->
        <!-- Header -->
        <div class="header bg-primary pb-8 mt-6">
            <div class="container-fluid">
                <div class="header-body">
                    {{-- <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
              <h6 class="h2 text-white d-inline-block mb-0">Tables</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i></a></li>
                  <li class="breadcrumb-item"><a href="#">Tables</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Tables</li>
                </ol>
              </nav>
            </div>
            <div class="col-lg-6 col-5 text-right">
              <a href="#" class="btn btn-sm btn-neutral">New</a>
              <a href="#" class="btn btn-sm btn-neutral">Filters</a>
            </div>
          </div> --}}
                </div>
            </div>
        </div>
        <!-- Page content -->
        <div class="container-fluid mt--6">
            <div class="row">
                <div class="col">
                    <div class="card">
                    <!-- Card header -->
                    <div class="card-header border-0">
                        <div class="row">
                            <div class="col">
                                <h3 class="mb-0">No. Of Users</h3>
                            </div>

                            <a type="button" class="float-right" id="serchOpenbtn">
                                <i class="fas fa-search"></i>
                                <span class="text-primary">Search</span>
                            </a>
                        </div>


                    </div>
                    <div class="card-header border-0" id="searchDiv" style="display: none;">
                        <form id="listForm" class="form-horizontal bv-form" method="post" novalidate
                            enctype='multipart/form-data'>
                            @csrf
                            <div class="search-container">
                                <div class="search-sec" id="searchPanel">
                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-lg-2 col-sm-1 col-sm-12">userName </label>
                                            <span class="colon">:</span>
                                            <div class="col-lg-3 col-md-6 col-sm-12">

                                                <input type="text" id="txtuserName" name="txtuserName"
                                                    class="form-control arabic" maxlength="50" autocomplete="off" value="@php if(isset($txtuserName)) echo $txtuserName @endphp">
                                            </div>

                                            <label class="col-lg-2 col-md-2 col-sm-12 ">Google Id </label>
                                            <span class="colon">:</span>
                                            <div class="col-lg-3 col-md-6 col-sm-12">

                                                <input type="text" id="txtGoogleId" name="txtGoogleId"
                                                    class="form-control arabic" maxlength="50" autocomplete="off" value="@php if(isset($txtGoogleId)) echo $txtGoogleId @endphp">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-lg-2 col-md-4 col-sm-12"></div>
                                            <div class="col-lg-3 col-md-3 col-sm-3">
                                                {{csrf_field()}}
                                                <input type="hidden" name="search" value="true" />
                                                <button class="btn btn-primary" name="btnSearch" type="submit"
                                                   > Search</button>
                                                @if($openFlag == 'S')
                                                <button class="btn btn-danger" name="btnReset" type="button"
                                                    onclick="location.replace(location.pathname);"> Reset</button>
                                                @else
                                                <button class="btn btn-danger" name="btnReset" type="reset"> Reset</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                        <div class="text-center"> <a type="button" class="searchDivClose">
                                <i class="ni ni-fat-remove text-danger  " style="font-size: 25px"></i>
                                {{-- <span class="text-danger">Close</span> --}}
                            </a></div>


                    </div>
                    <!-- Light table -->
                    <div class="table-responsive ">
                        @if($arrRecordCount >0)
                        <?php $i=1; 
                        ?>

                        <table class="table align-items-center table-flush">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>UserName</th>
                                    <th>Email</th>
                                    <th>Google_Id</th>
                                    <th>user_type</th>
                                    <th>created_at</th>
                                    <th>updated_at</th>
                                    <th>action</th>

                                </tr>
                            </thead>
                            <tbody class="list">
                                @foreach($arrRecords as $value)
                                <?php 
                                $arrParam =[];
                                $arrParam['name']= $value->name;
                                $arrParam['email']= $value->email;
                                $arrParam['google_id']= $value->google_id;
                                $arrParam['user_type']= $value->user_type;
                                $arrParam['created_at']= $value->created_at;
                                $arrParam['updated_at']= $value->updated_at;
                                $strPara=Crypt::encrypt($arrParam);
                                $strParam=str_replace('=', ' ', $strPara);

                                // $strParam =  encparam(json_encode($arrParam));
                                ?>
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>{{ $value->name }}</td>
                                    <td>{{ $value->email }}</td>
                                    <td>{{ $value->google_id > 0 ? $value->google_id : '--'}}</td>
                                    <td>{{ $value->user_type }}</td>
                                    <td>{{ $value->created_at }}</td>
                                    <td>{{ $value->updated_at }}</td>
                                    <td><a class="btn btn-success" href="{{url('Admin/'. $cont . '/view/' .$strParam)}}">View</a></td>
                                </tr>
                                <?php $i++; ?>
                                @endforeach

                            </tbody>
                        </table>
                        @else
                        <div class="noRecord text-center">No Record Found</div>
                        @endif
                    </div>
                    {!! $arrRecords->links() !!}
                    </div>
                </div>
            </div>
        </div>
        <!-- Dark table -->

        <!-- Footer -->
        @include('layouts.footer')
    </div>



    </div>
</body>