<?php
 /**
 * The base AppController file for other controllers to extend and use
 * Created On   : 08-Feb-2018
 * Note         : Do not modify code in this file without the consent of the author
 * 
 * ======================================================================
 * |Update History                                                      |
 * ======================================================================
 * |<Updated by>            |<Updated On> |<Remarks>                    |
 * ----------------------------------------------------------------------
 * |Name Goes Here          |01-Jan-2018  |Remark goes here        
 * ----------------------------------------------------------------------
 * |                        |             |                  
 * ----------------------------------------------------------------------
 * 
 * @package AppController
 * @author  Jabahar Mohapatra
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Filesystem;
use App\Models\IfmsLogDetailsModel;
use App\Models\ApplicationModel;
use Illuminate\Support\Facades\DB;
//require_once './vendor/autoload.php';
//use phpseclib\Net\SFTP;
use DOMDocument;
use DOMAttr;
use ZipArchive;
use Response;
use Illuminate\Support\Facades\Config;

class AppController extends BaseController
{
    /**
     * The request object
     *
     * @var object
     */
    protected $request;

    /**
     * The variable to store variables to be passed to the view
     *
     * @var array
     */
    protected $viewVars = ['intPageNo' => 1, 'isPaging'=>1, 'intRecsPerPage' => 10, 'arrPaging' => [], 'openFlag' => 'C', 'arrRecs' => []];

    /**
     * Stores the search conditions (in list view pages)
     *
     * @var array
     */
    protected $conditions = [];

    

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $objRequest)
    {

        $this->request = $objRequest;

        

        $arrControllerParts = explode('\\', get_class($this));
        $strControllerName = array_pop($arrControllerParts);

        $strModelClass = 'App\Models\\' . str_replace('Controller', 'Model', $strControllerName);

        if (class_exists($strModelClass)) {
            $this->model = new $strModelClass;
        }

        if (request('hdn_PageNo')) {

            $this->viewVars['intPageNo'] = request('hdn_PageNo');

        }

        if (null !== $this->request->input('hdn_IsPaging')) {            
            
            $this->viewVars['isPaging'] = request('hdn_IsPaging');

        }


    }
    //========= Check Special Character ==============
    public function isSpclChar($strToCheck)
    {   
        $arrySplChar    = explode(',',SPLCHRS);     
        $errFlag        = 0;
        for ($i=0; $i<count($arrySplChar); $i++)
        {
            $intPos=substr_count($strToCheck,trim($arrySplChar[$i]));
            if ($intPos>0)
                $errFlag++; 
        }
        return $errFlag;
    }
    /*==============function to check blank value ==================
            By  : Ashok Kumar Samal
            ON  : 05-05-2018
    ================================================================*/
    public function isBlank($strToCheck)
    {   
        $errFlag        = 1;
        if($strToCheck!='')
            $errFlag    = 0;
        return $errFlag;
    }
        public function IsNullOrEmptyString($question){
            $errFlag        = 0;
            if (!isset($question) || trim($question)==='')
            {
                $errFlag    = 1;
            }
            return $errFlag;
        }
    /*======== function to check Max, Min or Equal length ==========
            By  :Ashok Kumar Samal
            ON  : 05-05-2018
    ================================================================*/
    public function chkLength($flag, $strToCheck, $length)
    {   
        //======= $flag= 'MAX'/'MIN'/'EQ' for Maximum Minimum or Equal length
        $errFlag        = 0;
        if($strToCheck!='')
        {
            if(strtolower($flag)=='max')
            {
                if(strlen($strToCheck)>$length)
                    $errFlag        = 1;
            }
            else if(strtolower($flag)=='min')
            {
                if(strlen($strToCheck)<$length)
                    $errFlag        = 1;
            }
            else if(strtolower($flag)=='eq')
            {
                if(strlen($strToCheck)!=$length)
                    $errFlag        = 1;
            }   
        }   
        return $errFlag;
    }
    /*============== function to check dropdown field ==============
            By  : Ashok Kumar Samal
            ON  : 05-05-2018
    ================================================================*/
    public function chkDropdown($drpVal)
    {   
        $errFlag        = 1;
        if($drpVal>0 && $drpVal!='')
            $errFlag        = 0;
        return $errFlag;
    }
    /*============ function to check only numeric data =============
            By  : Ashok Kumar Samal
            ON  : 05-05-2018
    ================================================================*/
    public function isNumericData($data)
    {   
        $errFlag        = 1;
        if(preg_match('/^\d+$/',$data))
           $errFlag     = 0;
        return $errFlag;
    }
    /*============ function to check only character data =============
            By  : Ashok Kumar Samal
            ON  : 05-05-2018
    ================================================================*/
    public function isCharData($data)
    {   
        $errFlag        = 1;
        if(preg_match('/^[a-zA-Z.,-\s]+$/i',$data))
           $errFlag     = 0;
        return $errFlag;
    }
    /*============ function to check decimal data =============
            By  : Ashok Kumar Samal
            ON  : 05-05-2018
    ================================================================*/
    public function isDecimal($data,$afterDecimal=2)
    {           
        $errFlag        = 1;
        if(preg_match('/^[0-9]+(\.[0-9]{1,'.$afterDecimal.'})?$/',$data))
           $errFlag     = 0;
        return $errFlag;
    }


    /*======= function to Upload Single File Through samba File Upload with SFTP ==========
            By  : Ashok Kumar Samal
            ON  : 20-11-2019
    ================================================================*/
    /*public function sftpUploadFile($fileData, $ctrlName,$remoteFolderName='osspTestfile' )
    {   
        $server     = '192.168.101.203';
        $username   = 'ashok';
        $password   = 'csmpl@1234';
        $remotePath = '/eVidya-FS/ajit/uploadDocuments/';

        $postfileName = $fileData[$ctrlName]['name'];
        $postfileTmp  = $fileData[$ctrlName]['tmp_name'];
        $postFileSize = $fileData[$ctrlName]['size'];
        $postFileExt  = pathinfo($postfileName , PATHINFO_EXTENSION);
        $newFileDocNm = ($postfileName != '')?$remoteFolderName.'_'.time().'.'.$postFileExt:'';
        if(!empty($postfileName)) 
        {    
            //get filename without extension
            //$filename = pathinfo($postfileName, PATHINFO_FILENAME);
     
            //Storage::disk('sftp')->makeDirectory($remoteFolderName, 0755);

            // delete the file with same name before upload
            //Storage::disk('sftp')->delete($newFileDocNm);
    
            //Upload File to external server
            //Storage::disk('sftp')->put($remoteFolderName.'/'.$newFileDocNm, fopen($fileData, 'r+'));

            $sftp = new SFTP($server);
            if (!$sftp->login($username, $password)) 
            {
                throw new Exception('Login failed');
            }
            //$sftp->chdir('uploadDocuments'); // open directory 'test'

            //print_r($sftp);
            //$sftp->mkdir(fopen($remoteFolderName, 'r+'),'0755');    
            $sftp->put($remotePath.$remoteFolderName.'/'.$newFileDocNm, $postfileTmp);
            $files = $sftp->nlist();
            var_dump($files);
            $resArr = array('status' => 200, 'errMsg'=>'', 'uploadedFileName'=>$newFileDocNm);
        }
        else
        {
            $resArr = array('status' => 404, 'errMsg'=>'Invalid File Uploaded', 'uploadedFileName'=>'');
            //echo 'error';
        }

        return json_encode($resArr);
    }*/


    // public function sambaUploadFile($fileData, $ctrlName,$remoteFolderName='osspfile', $old_file)
    // {
        

    //     if($fileData->hasFile($ctrlName)) 
    //     {    
    //         $filenamewithextension = $fileData->file($ctrlName)->getClientOriginalName();
     
    //         //get filename without extension
    //         $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
     
    //         //get file extension
    //         $extension = $fileData->file($ctrlName)->getClientOriginalExtension();
                    
    //         //filename to store
    //         $filenametostore = $filename.'_'.time().'.'.$extension;
            
    //         Storage::disk('sftp')->makeDirectory($remoteFolderName, 0755);
            
    //         //Upload File to external server
    //         Storage::disk('sftp')->delete($remoteFolderName.'/'.$old_file, fopen($fileData->file($ctrlName), 'r+'));
    //         Storage::disk('sftp')->put($remoteFolderName.'/'.$filenametostore, fopen($fileData->file($ctrlName), 'r+'));
    //         $resArr = array('status' => 200, 'errMsg'=>'', 'uploadedFileName'=>$filenametostore);
    //     }
    //     else
    //     {
    //         $resArr = array('status' => 404, 'errMsg'=>'Invalid File Uploaded', 'uploadedFileName'=>'');
    //         //echo 'error';
    //     }

    //     return json_encode($resArr);
    // }

    public function sambaUploadFile($fileData, $ctrlName,$remoteFolderName='application', $old_file='', $newFileName='', $fromDigilocker = 0, $fileNameDigilocker = '')
    {
        try {
            //echo $fileData->hasFile($ctrlName);die;
            if($fileData->hasFile($ctrlName)) 
            {    
                $filenamewithextension = $fileData->file($ctrlName)->getClientOriginalName();
        
                //get filename without extension
                $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
        
                //get file extension
                $extension = $fileData->file($ctrlName)->getClientOriginalExtension();
                        
                //filename to store
                if($newFileName == ''){
                    $filenametostore = $filename.'_'.time().'.'.$extension;
                }else{
                    $filenametostore = $newFileName.'.'.$extension;
                }
                //Create directory if not present
                Storage::disk('sftp')->makeDirectory($remoteFolderName, 0755, true, true);

                //Delete Prev File
                if($old_file != ''){
                    Storage::disk('sftp')->delete($remoteFolderName.'/'.$old_file, fopen($fileData->file($ctrlName), 'r+'));
                }
                
                //Upload File to external server
                Storage::disk('sftp')->put($remoteFolderName.'/'.$filenametostore, fopen($fileData->file($ctrlName), 'r+'));

                $resArr = array('status' => 200, 'errMsg'=>'', 'uploadedFileName'=>$filenametostore);
            }else{
                if($fromDigilocker > 0){
                    //Upload File to external server
                    Storage::disk('sftp')->makeDirectory($remoteFolderName, 0755, true, true);
                    Storage::disk('sftp')->put($remoteFolderName.'/'.$fileNameDigilocker, fopen(storage_path('app/uploadDocuments/digilocker/'.$fileNameDigilocker), 'r+'));

                    $resArr = array('status' => 200, 'errMsg'=>'', 'uploadedFileName'=>$fileNameDigilocker);
                }else{
                    $resArr = array('status' => 404, 'errMsg'=>'Invalid File Uploaded', 'uploadedFileName'=>$old_file);
                }
            }
            
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $resArr = array('status' => 402, 'errMsg'=>'Invalid File Uploaded', 'uploadedFileName'=>$old_file);
        }
        return json_encode($resArr);
    }


     /*=== function to view Uploaded Single File Through samba File Upload with SFTP =====
            By  : Ashok Kumar Samal
            ON  : 20-11-2019
    ================================================================*/
    public function viewUploadedFile($fileDocName,$remoteFolderName, $width='', $height='')
    {
        try {
            //Store $filenametostore in the database
            $fileDocName = trim($fileDocName);
            $checkExists = Storage::disk('sftp')->exists($remoteFolderName.'/'.$fileDocName);
            if($checkExists) {
                $contents = Storage::disk('sftp')->get($remoteFolderName.'/'.$fileDocName);
                $fileData = base64_encode($contents); 
                $file = explode(".", $fileDocName);
               
                if($file[1] == 'jpg' || $file[1] == 'jpeg' || $file[1] == 'png' || $file[1] == 'gif'){
                    $display = '<img class="border" src="data:application/pdf;data:image/*;base64,'.base64_encode($contents) .'" width="'.$width.'" height="'.$height.'">';
                }else{
                    $display =  '<object type="image/jpeg" data="data:application/pdf;data:image/*;base64,'.base64_encode($contents).'" width="'.$width.'" height="'.$height.'"></object>';    
                }
                
                $resArr = array('status' => 200, 'errMsg'=>'', 'uploadedFileData'=>$fileData, 'uploadedFileSample'=>$display);
            }
            else {
                $resArr = array('status' => 402, 'errMsg'=>'File not found', 'uploadedFileData'=>'', 'uploadedFileSample'=>'');
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $resArr = array('status' => 402, 'errMsg'=>$error, 'uploadedFileData'=>'', 'uploadedFileSample'=>'');
        }
        return json_encode($resArr);
    }

    /*=== function to Upload Single File to IFMS with SFTP =====
            By  : Abhijit Sahoo
            ON  : 12-12-2019
    ================================================================*/
    public function ifmsUploadFile($fileName, $remoteFolderName='EPB')
    {
        //Upload File to IFMS server
        $file =  storage_path('app/public/'.$fileName);
        try {
            Storage::disk('ifms')->put($remoteFolderName.'/'.$fileName, fopen($file, 'r+'));
            $resArr = array('status' => 200, 'errMsg'=>'', 'uploadedFileName'=>'');
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $resArr = array('status' => 200, 'errMsg'=>$error, 'uploadedFileName'=>'');
        }
        return json_encode($resArr);
    }

     /*=== function to get Uploaded File from IFMS with SFTP =====
            By  : Abhijit Sahoo
            ON  : 18-12-2019
    ================================================================*/
    public function ifmsFileCheck()
    {
        try {
            //Check through COR File
            $fileDataCor = IfmsLogDetailsModel::where('vch_File_Type', 'COR')
                        ->where('int_Status', 1)
                        //->whereRaw("DATE_FORMAT(dtm_created_on, '%Y-%m-%d') = ?", [date('Y-m-d')])
                        ->get();

            foreach ($fileDataCor as $key => $value) {
                $fileName = $value['vch_File_Name'];

                // CORACK Folder check
                $checkCorack = Storage::disk('ifms')->exists('CORACK/ACK'.$fileName.'.zip');
                if($checkCorack) {
                    Storage::disk('public')->put('ACK'.$fileName.'.zip', Storage::disk('ifms')->get('CORACK/ACK'.$fileName.'.zip'));
                    $ifmsCheck = IfmsLogDetailsModel::where('vch_File_Type', 'CORACK')
                                    ->where('vch_File_Name', 'ACK'.$fileName)
                                    ->where('bit_Deleted_Flag', 0)
                                    ->count();
                    
                    if($ifmsCheck == 0){
                        // $ifmsUpdate = IfmsLogDetailsModel::find($value['int_log_Id']);
                        // $ifmsUpdate->int_Status = 0;
                        // $ifmsUpdate->save();

                        //Unzip file
                        $za = new \ZipArchive();
                        $za->open('storage/app/public/ACK'.$fileName.'.zip'); 
                        $za->extractTo('storage/app/public/');
                        $za->close();

                        //Get error Code
                        $xml = simplexml_load_file('./storage/app/public/ACK'.$fileName.'.xml');
                        $corCode = $xml->correctionStatus->codes->code;
                        $list = $xml->billDetail;
                        if(count($list) > 0) {
                            for ($i = 0; $i < count($list); $i++) {
                                $list1 = $list[$i]->beneficiaries->beneficiary;
                                for ($j = 0; $j < count($list1); $j++) {
                                    $benfId = $list1[$j]->orgBenfId;
                                    $payStatus = $list1[$j]->codes->code;
                                    $nbenId = substr_replace( $benfId, 'OS', 2, 0 );//substr($benfId, 2);
                                    $applicationId = DB::table('t_application as a')
                                                    ->join('t_students as b', 'a.int_Student_Id', '=', 'b.int_Student_Id')
                                                    ->where('b.vch_Student_Unique_No', $nbenId)
                                                    ->where('a.int_Disbursed_Status', 2)
                                                    ->first();
    
                                    $application = ApplicationModel::find($applicationId->int_Application_Id);
                                    $application->vch_Pay_Status = $payStatus;
                                    $application->vch_Error_Code = $payStatus;
                                    $application->save();   
                                }
                            }
                        }else{

                        }
                        
                        $ifmsLog = new IfmsLogDetailsModel();
                        $ifmsLog->int_Dept_Code = $value['int_Dept_Code'];
                        $ifmsLog->int_Service_code = $value['int_Service_code'];
                        $ifmsLog->int_Ref_Id = $value['int_Ref_Id'];
                        $ifmsLog->int_Serial_No = $value['int_Serial_No'];
                        $ifmsLog->vch_File_Name = 'ACK'.$fileName;
                        $ifmsLog->vch_File_Type = "CORACK";
                        $ifmsLog->vch_error_code = $corCode;
                        $ifmsLog->vch_sanction_order_no = $value['vch_sanction_order_no'];
                        $ifmsLog->int_Status = 1;
                        $ifmsLog->save();

                        Storage::disk('ifms')->move('CORACK/ACK'.$fileName.'.zip', 'CORACK/DONE/ACK'.$fileName.'.zip');
			            echo 'corack';
                    }
                    continue;
                }
                
                // CORNCK Folder check
                $checkCornck = Storage::disk('ifms')->exists('CORNCK/NCK'.$fileName.'.zip');
                if($checkCornck) {
                    Storage::disk('public')->put('NCK'.$fileName.'.zip', Storage::disk('ifms')->get('CORNCK/NCK'.$fileName.'.zip'));
                    $ifmsCheck = IfmsLogDetailsModel::where('vch_File_Type', 'CORNCK')
                                    ->where('vch_File_Name', 'NCK'.$fileName)
                                    ->where('bit_Deleted_Flag', 0)
                                    ->count();
                   
                    if($ifmsCheck == 0){
                        $ifmsUpdate = IfmsLogDetailsModel::find($value['int_log_Id']);
                        $ifmsUpdate->int_Status = 0;
                        $ifmsUpdate->save();
			
                        //Unzip file
                        $za = new \ZipArchive();
                        $za->open('storage/app/public/NCK'.$fileName.'.zip'); 
                        $za->extractTo('storage/app/public/');
                        $za->close();

                        //Get error Code
                        $xml = simplexml_load_file('./storage/app/public/NCK'.$fileName.'.xml');
                        $list = $xml->billDetail;
                        for ($i = 0; $i < count($list); $i++) {
                            $error = $list[$i]->billStatus->codes->code;
                        }
                        
                        $ifmsLog = new IfmsLogDetailsModel();
                        $ifmsLog->int_Dept_Code = $value['int_Dept_Code'];
                        $ifmsLog->int_Service_code = $value['int_Service_code'];
                        $ifmsLog->int_Ref_Id = $value['int_Ref_Id'];
                        $ifmsLog->int_Serial_No = $value['int_Serial_No'];
                        $ifmsLog->vch_File_Name = 'NCK'.$fileName;
                        $ifmsLog->vch_File_Type = "CORNCK";
                        $ifmsLog->vch_error_code = $error;
                        $ifmsLog->vch_sanction_order_no = $value['vch_sanction_order_no'];
                        $ifmsLog->int_Status = 1;
                        $ifmsLog->save();

                        Storage::disk('ifms')->move('CORNCK/NCK'.$fileName.'.zip', 'CORNCK/DONE/NCK'.$fileName.'.zip');
			            echo 'cornck';
                    }
                    continue;
                }
            }
            
            //Check through EPB File                        
            $fileData = IfmsLogDetailsModel::where('vch_File_Type', 'EPB')
                        ->where('int_Status', 1)
                        //->whereRaw("DATE_FORMAT(dtm_created_on, '%Y-%m-%d') = ?", [date('Y-m-d')])
                        ->get();
                     //dd($fileData);   
            foreach ($fileData as $key => $value) {
               $fileName = $value['vch_File_Name'];
               $paymentTo = $value['tin_File_Type'];


                // RET Folder check
                $slNoRet = IfmsLogDetailsModel::where('vch_File_Type', 'RET')
                            ->where('vch_File_Name', 'like', '%RET'.$fileName.'%')
                            ->where('int_Status', 1)
                            ->where('bit_Deleted_Flag', 0)
                            ->orderBy('int_log_Id', 'DESC')
                            ->first();

                if(count($slNoRet) > 0){
                    $flSlNoRet = str_pad($slNoRet->vch_Ifms_Serial_No + 1, 3, "0", STR_PAD_LEFT);
                    $logId = $slNoRet->int_log_Id;

                    // $ifmsUpdateRet = IfmsLogDetailsModel::find($logId);
                    // $ifmsUpdateRet->int_Status = 0;
                    // $ifmsUpdateRet->save();

                }else{
                    $flSlNoRet = '001';
                }
                
                $checkRet = Storage::disk('ifms')->exists('RET/RET'.$fileName.$flSlNoRet.'.zip');
                if($checkRet) {
                    Storage::disk('public')->put('RET'.$fileName.$flSlNoRet.'.zip', Storage::disk('ifms')->get('RET/RET'.$fileName.$flSlNoRet.'.zip'));
                    $ifmsCheck = IfmsLogDetailsModel::where('vch_File_Type', 'RET')
                                    ->where('vch_File_Name', 'RET'.$fileName.$flSlNoRet)
                                    ->where('bit_Deleted_Flag', 0)
                                    ->get();
        
                    if(count($ifmsCheck) == 0){
                        // $ifmsUpdateData = IfmsLogDetailsModel::where('vch_sanction_order_no', $value['vch_sanction_order_no'])->get();
                        // foreach($ifmsUpdateData as $iud){
                        //     $ifmsUpdate = IfmsLogDetailsModel::find($iud->int_log_Id);
                        //     $ifmsUpdate->int_Status = 0;
                        //     $ifmsUpdate->save();
                        // }
                        
                        //Unzip file
                        $za = new \ZipArchive();
                        $za->open('storage/app/public/RET'.$fileName.$flSlNoRet.'.zip'); 
                        $za->extractTo('storage/app/public/');
                        $za->close();

                        //Get error Code
                        $xml = simplexml_load_file('./storage/app/public/RET'.$fileName.$flSlNoRet.'.xml');
                        $list = $xml->beneficiaries->beneficiary;
                        for ($i = 0; $i < count($list); $i++) {
                            $benfId = $list[$i]->benfId;
                            $utrNo = $list[$i]->utrNo;
                            $utrDate = $list[$i]->utrDate;
                            $payStatus = $list[$i]->payStatus;
                            $curBillRefNo = $list[$i]->curBillRefNo;
                            $statusDesc = $list[$i]->statusDesc;
                            $nbenId = substr_replace( $benfId, 'OS', 2, 0 );//substr($benfId, 2);
                            $applicationId = DB::table('t_application as a')
                                        ->join('t_students as b', 'a.int_Student_Id', '=', 'b.int_Student_Id')
                                        ->where('b.vch_Student_Unique_No', $nbenId)
                                        // ->where('a.int_Disbursed_Status', 2)
                                        ->first();

                            $application = ApplicationModel::find($applicationId->int_Application_Id);
                            $application->vch_Utr_No = $utrNo;
                            $application->dte_Utr_Date = $utrDate;
                            $application->vch_Pay_Status = $payStatus;
                            $application->vch_Curr_Bill_Ref_No = $curBillRefNo;
                            $application->vch_Status_Desc = $statusDesc;
                            $application->vch_Status_Desc = $statusDesc;
                            $application->int_Disbursed_Status = 2;
                            $application->save();                            
                        }

                        $list1 = $xml->billDetail;
                        for ($i = 0; $i < count($list1); $i++) {
                            $orgBillRefNo = $list1[$i]->orgBillRefNo;
                            $orgFileSlNo = $list1[$i]->orgFileSlNo;
                        }

                        $ifmsLog = new IfmsLogDetailsModel();
                        $ifmsLog->int_Dept_Code = $value['int_Dept_Code'];
                        $ifmsLog->int_Service_code = $value['int_Service_code'];
                        $ifmsLog->int_Ref_Id = $value['int_Ref_Id'];
                        $ifmsLog->int_Serial_No = $value['int_Serial_No'];
                        $ifmsLog->vch_File_Name = 'RET'.$fileName.$flSlNoRet;
                        $ifmsLog->vch_File_Type = "RET";
                        $ifmsLog->tin_File_Type = $paymentTo;
                        $ifmsLog->vch_Ifms_Serial_No = $flSlNoRet;
                        $ifmsLog->vch_sanction_order_no = $value['vch_sanction_order_no'];
                        $ifmsLog->vch_Bill_Ref_No = $orgBillRefNo;
                        $ifmsLog->vch_Org_File_Slno = $orgFileSlNo;
                        $ifmsLog->int_Status = 1;
                        $ifmsLog->save();

                        Storage::disk('ifms')->move('RET/RET'.$fileName.$flSlNoRet.'.zip', 'RET/DONE/RET'.$fileName.$flSlNoRet.'.zip');
			            echo 'ret';
                    }
                    continue;
                }

                // STS Folder check
                $slNoSts = IfmsLogDetailsModel::where('vch_File_Type', 'STS')
                            ->where('vch_File_Name', 'like', '%STS'.$fileName.'%')
                            ->where('int_Status', 1)
                            ->where('bit_Deleted_Flag', 0)
                            ->orderBy('int_log_Id', 'DESC')
                            ->first();

                if(count($slNoSts) > 0){
                    $flSlNoSts = str_pad($slNoSts->vch_Ifms_Serial_No + 1, 3, "0", STR_PAD_LEFT);
                    $logId = $slNoSts->int_log_Id;

                    // $ifmsUpdateSts = IfmsLogDetailsModel::find($logId);
                    // $ifmsUpdateSts->int_Status = 0;
                    // $ifmsUpdateSts->save();

                }else{
                    $flSlNoSts = '001';
                }

                $checkSts = Storage::disk('ifms')->exists('STS/STS'.$fileName.$flSlNoSts.'.zip');
                if($checkSts) {
                    Storage::disk('public')->put('STS'.$fileName.$flSlNoSts.'.zip', Storage::disk('ifms')->get('STS/STS'.$fileName.$flSlNoSts.'.zip'));
                    $ifmsCheck = IfmsLogDetailsModel::where('vch_File_Type', 'STS')
                                    ->where('vch_File_Name', 'STS'.$fileName.$flSlNoSts)
                                    ->where('bit_Deleted_Flag', 0)
                                    ->get();
        
                    if(count($ifmsCheck) == 0){    
                        //Unzip file
                        $za = new \ZipArchive();
                        $za->open('storage/app/public/STS'.$fileName.$flSlNoSts.'.zip'); 
                        $za->extractTo('storage/app/public/');
                        $za->close();

                        //Get error Code
                        $xml = simplexml_load_file('./storage/app/public/STS'.$fileName.$flSlNoSts.'.xml');

                        $list1 = $xml->billDetail;
                        for ($i = 0; $i < count($list1); $i++) {
                            $orgBillRefNo = $list1[$i]->orgBillRefNo;
                            $voucherNo = $list1[$i]->voucherNo;
                            $voucherDate = $list1[$i]->voucherDate;
                        }
						
                        $list = $xml->beneficiaries->beneficiary;
						
                        for ($i = 0; $i < count($list); $i++) {
                            $benfId = $list[$i]->benfId;
                            $utrNo = $list[$i]->utrNo;
                            $utrDate = $list[$i]->utrDate;
                            $payStatus = $list[$i]->payStatus;
                            $curBillRefNo = $list[$i]->curBillRefNo;
                            $statusDesc = $list[$i]->statusDesc;
                            $nbenId = substr_replace( $benfId, 'OS', 2, 0 );//substr($benfId, 2);
                            $applicationId = DB::table('t_application as a')
                                        ->join('t_students as b', 'a.int_Student_Id', '=', 'b.int_Student_Id')
                                        ->where('b.vch_Student_Unique_No', $nbenId)
                                        ->where('a.int_Disbursed_Status', 2)
                                        ->first();

                            $application = ApplicationModel::find($applicationId->int_Application_Id);
                            $application->vch_Utr_No = $utrNo;
                            $application->dte_Utr_Date = $utrDate;
                            $application->vch_Pay_Status = $payStatus;
                            $application->vch_Curr_Bill_Ref_No = $curBillRefNo;
                            $application->vch_Status_Desc = $statusDesc;
                            $application->vch_Error_Code = $payStatus;
                            $application->vch_Voucher_No = $voucherNo;
                            $application->dte_Voucher_Date = $voucherDate;
                            if($payStatus == '000000'){
                                $application->int_Disbursed_Status = 3;
                            }
                            $application->save();                            
                        }
                        
                        $ifmsLog = new IfmsLogDetailsModel();
                        $ifmsLog->int_Dept_Code = $value['int_Dept_Code'];
                        $ifmsLog->int_Service_code = $value['int_Service_code'];
                        $ifmsLog->int_Ref_Id = $value['int_Ref_Id'];
                        $ifmsLog->int_Serial_No = $value['int_Serial_No'];
                        $ifmsLog->vch_File_Name = 'STS'.$fileName.$flSlNoSts;
                        $ifmsLog->vch_File_Type = "STS";
                        $ifmsLog->tin_File_Type = $paymentTo;
                        $ifmsLog->vch_Ifms_Serial_No = $flSlNoSts;
                        $ifmsLog->vch_sanction_order_no = $value['vch_sanction_order_no'];
                        $ifmsLog->vch_Bill_Ref_No = $orgBillRefNo;
                        $ifmsLog->int_Status = 1;
                        $ifmsLog->save();

                        Storage::disk('ifms')->move('STS/STS'.$fileName.$flSlNoSts.'.zip', 'STS/DONE/STS'.$fileName.$flSlNoSts.'.zip');
			            echo 'sts';
                    }
                    continue;
                }

                // OBJ Folder check
                $checkObj = Storage::disk('ifms')->exists('OBJ/OBJ'.$fileName.'.zip');
                if($checkObj) {
                    Storage::disk('public')->put('OBJ'.$fileName.'.zip', Storage::disk('ifms')->get('OBJ/OBJ'.$fileName.'.zip'));
                    $ifmsCheck = IfmsLogDetailsModel::where('vch_File_Type', 'OBJ')
                                    ->where('vch_File_Name', 'OBJ'.$fileName)
                                    ->where('bit_Deleted_Flag', 0)
                                    ->count();
                    
                    if($ifmsCheck == 0){
                        $ifmsUpdate = IfmsLogDetailsModel::find($value['int_log_Id']);
                        $ifmsUpdate->int_Status = 0;
                        $ifmsUpdate->save();

                        //Unzip file
                        $za = new \ZipArchive();
                        $za->open('storage/app/public/OBJ'.$fileName.'.zip'); 
                        $za->extractTo('storage/app/public/');
                        $za->close();

                        //Get error Code
                        $xml = simplexml_load_file('./storage/app/public/OBJ'.$fileName.'.xml');
                        $list = $xml->billDetail;
                        for ($i = 0; $i < count($list); $i++) {
                            $billRef = $list[$i]->orgBillRefNo;
                            $error = $list[$i]->billStatus->codes->code;
                        }

                        $ifmsLog = new IfmsLogDetailsModel();
                        $ifmsLog->int_Dept_Code = $value['int_Dept_Code'];
                        $ifmsLog->int_Service_code = $value['int_Service_code'];
                        $ifmsLog->int_Ref_Id = $value['int_Ref_Id'];
                        $ifmsLog->int_Serial_No = $value['int_Serial_No'];
                        $ifmsLog->vch_File_Name = 'OBJ'.$fileName;
                        $ifmsLog->vch_File_Type = "OBJ";
                        $ifmsLog->tin_File_Type = $paymentTo;
                        $ifmsLog->vch_error_code = $error;
                        $ifmsLog->vch_Bill_Ref_No = $billRef;
                        $ifmsLog->vch_sanction_order_no = $value['vch_sanction_order_no'];
                        $ifmsLog->int_Status = 1;
                        $ifmsLog->save();
                        
                        $application = DB::table('t_application')->where('vch_Approval_Order_No', $value['vch_sanction_order_no'])->update(['vch_Error_Code' => $error, 'int_Disbursed_Status' => 2]);

                        Storage::disk('ifms')->move('OBJ/OBJ'.$fileName.'.zip', 'OBJ/DONE/OBJ'.$fileName.'.zip');
			            echo 'obj';
                    }
                    continue;
                }
             
                // EPBACK Folder check
                $checkEpback = Storage::disk('ifms')->exists('EPBACK/ACK'.$fileName.'.zip');
                if($checkEpback) {
                    Storage::disk('public')->put('ACK'.$fileName.'.zip', Storage::disk('ifms')->get('EPBACK/ACK'.$fileName.'.zip'));
                    $ifmsCheck = IfmsLogDetailsModel::where('vch_File_Type', 'EPBACK')
                                    ->where('vch_File_Name', 'ACK'.$fileName)
                                    ->where('bit_Deleted_Flag', 0)
                                    ->count();
                    //update t_ifms_log set bit_Deleted_Flag = 1 where int_log_Id = 29
                    if($ifmsCheck == 0){
                        // $ifmsUpdate = IfmsLogDetailsModel::find($value['int_log_Id']);
                        // $ifmsUpdate->int_Status = 0;
                        // $ifmsUpdate->save();

                        //Unzip file
                        $za = new \ZipArchive();
                        $za->open('storage/app/public/ACK'.$fileName.'.zip'); 
                        $za->extractTo('storage/app/public/');
                        $za->close();

                        //Get error Code
                        $xml = simplexml_load_file('./storage/app/public/ACK'.$fileName.'.xml');
                        $list = $xml->billDetail;
                        for ($i = 0; $i < count($list); $i++) {
                            $error = $list[$i]->billStatus->codes->code;
                        }

                        $list = $xml->beneficiaries->beneficiary;
                        if(count($list) > 0){
                            for ($i = 0; $i < count($list); $i++) {
                                $benfId = $list[$i]->benfId;
                                $payStatus = $list[$i]->codes->code;
                                $nbenId = substr_replace( $benfId, 'OS', 2, 0 );//substr($benfId, 2);
    // echo $nbenId;                            
                                $applicationId = DB::table('t_application as a')
                                            ->join('t_students as b', 'a.int_Student_Id', '=', 'b.int_Student_Id')
                                            ->where('b.vch_Student_Unique_No', $nbenId)
                                            ->where('a.int_Disbursed_Status', 2)
                                            ->where('a.bit_Deleted_Flag', 0)
                                            ->first();
    // echo $payStatus;die;
                                $application = ApplicationModel::find($applicationId->int_Application_Id);
                                $application->vch_Error_Code = $payStatus;
                                $application->int_Disbursed_Status = 5;
                                $application->save();                            
                            }
                        }else{
                            $application = DB::table('t_application')->where('vch_Approval_Order_No', $value['vch_sanction_order_no'])->update(['vch_Error_Code' => $error, 'int_Disbursed_Status' => 2]);
                        }
						
                        

                        $ifmsLog = new IfmsLogDetailsModel();
                        $ifmsLog->int_Dept_Code = $value['int_Dept_Code'];
                        $ifmsLog->int_Service_code = $value['int_Service_code'];
                        $ifmsLog->int_Ref_Id = $value['int_Ref_Id'];
                        $ifmsLog->int_Serial_No = $value['int_Serial_No'];
                        $ifmsLog->vch_File_Name = 'ACK'.$fileName;
                        $ifmsLog->vch_File_Type = "EPBACK";
                        $ifmsLog->tin_File_Type = $paymentTo;
                        $ifmsLog->vch_error_code = $error;
                        $ifmsLog->vch_sanction_order_no = $value['vch_sanction_order_no'];
                        $ifmsLog->int_Status = 1;
                        $ifmsLog->save();

                        // $application = DB::table('t_application')->where('vch_Approval_Order_No', $value['vch_sanction_order_no'])->update(['vch_Error_Code' => $error, 'int_Disbursed_Status' => 2]);

                        Storage::disk('ifms')->move('EPBACK/ACK'.$fileName.'.zip', 'EPBACK/DONE/ACK'.$fileName.'.zip');
			            echo 'ack';
                    }
                    continue;
                }
                
                // EPBNCK Folder check
                $checkEpbnck = Storage::disk('ifms')->exists('EPBNCK/NCK'.$fileName.'.zip');
                if($checkEpbnck) {
                    Storage::disk('public')->put('NCK'.$fileName.'.zip', Storage::disk('ifms')->get('EPBNCK/NCK'.$fileName.'.zip'));
                    $ifmsCheck = IfmsLogDetailsModel::where('vch_File_Type', 'EPBNCK')
                                    ->where('vch_File_Name', 'NCK'.$fileName)
                                    ->where('bit_Deleted_Flag', 0)
                                    ->count();
                   
                    if($ifmsCheck == 0){
                        $ifmsUpdate = IfmsLogDetailsModel::find($value['int_log_Id']);
                        $ifmsUpdate->int_Status = 0;
                        $ifmsUpdate->save();
			
                        //Unzip file
                        $za = new \ZipArchive();
                        $za->open('storage/app/public/NCK'.$fileName.'.zip'); 
                        $za->extractTo('storage/app/public/');
                        $za->close();

                        //Get error Code
                        $xml = simplexml_load_file('./storage/app/public/NCK'.$fileName.'.xml');
                        $list = $xml->billDetail;
                        for ($i = 0; $i < count($list); $i++) {
                            $error = $list[$i]->billStatus->codes->code;
                        }
                        
                        $ifmsLog = new IfmsLogDetailsModel();
                        $ifmsLog->int_Dept_Code = $value['int_Dept_Code'];
                        $ifmsLog->int_Service_code = $value['int_Service_code'];
                        $ifmsLog->int_Ref_Id = $value['int_Ref_Id'];
                        $ifmsLog->int_Serial_No = $value['int_Serial_No'];
                        $ifmsLog->vch_File_Name = 'NCK'.$fileName;
                        $ifmsLog->vch_File_Type = "EPBNCK";
                        $ifmsLog->tin_File_Type = $paymentTo;
                        $ifmsLog->vch_error_code = $error;
                        $ifmsLog->vch_sanction_order_no = $value['vch_sanction_order_no'];
                        $ifmsLog->int_Status = 1;
                        $ifmsLog->save();

                        $application = DB::table('t_application')->where('vch_Approval_Order_No', $value['vch_sanction_order_no'])->update(['vch_Error_Code' => $error, 'int_Disbursed_Status' => 2]);
                        
                        Storage::disk('ifms')->move('EPBNCK/NCK'.$fileName.'.zip', 'EPBNCK/DONE/NCK'.$fileName.'.zip');
			            echo 'nck';
                    }
                    continue;
                }

                // RCV Folder check
                $checkRcv = Storage::disk('ifms')->exists('RCV/RCV'.$fileName.'.zip');
                if($checkRcv) {
                    Storage::disk('public')->put('RCV'.$fileName.'.zip', Storage::disk('ifms')->get('RCV/RCV'.$fileName.'.zip'));
                    $ifmsCheck = IfmsLogDetailsModel::where('vch_File_Type', 'RCV')
                                    ->where('vch_File_Name', 'RCV'.$fileName)
                                    ->where('bit_Deleted_Flag', 0)
                                    ->count();
                    
                    if($ifmsCheck == 0){
                        // $ifmsUpdate = IfmsLogDetailsModel::find($value['int_log_Id']);
                        // $ifmsUpdate->int_Status = 0;
                        // $ifmsUpdate->save();

                        //Unzip file
                        $za = new \ZipArchive();
                        $za->open('storage/app/public/RCV'.$fileName.'.zip'); 
                        $za->extractTo('storage/app/public/');
                        $za->close();

                        //Get error Code
                        $xml = simplexml_load_file('./storage/app/public/RCV'.$fileName.'.xml');
                        $list = $xml->billDetail;
                        for ($i = 0; $i < count($list); $i++) {
                            $billRef = $list[$i]->billRefNo;
                            $tokenNo = $list[$i]->tokenNumber;
                            $tokenDate = $list[$i]->tokenDate;
                            $sanctionNo = $list[$i]->sanctionNo;
                            $sanctionDate = $list[$i]->sanctionDate;
                        }

                        $ifmsLog = new IfmsLogDetailsModel();
                        $ifmsLog->int_Dept_Code = $value['int_Dept_Code'];
                        $ifmsLog->int_Service_code = $value['int_Service_code'];
                        $ifmsLog->int_Ref_Id = $value['int_Ref_Id'];
                        $ifmsLog->int_Serial_No = $value['int_Serial_No'];
                        $ifmsLog->vch_File_Name = 'RCV'.$fileName;
                        $ifmsLog->vch_File_Type = "RCV";
                        $ifmsLog->tin_File_Type = $paymentTo;
                        $ifmsLog->vch_Bill_Ref_No = $billRef;
                        $ifmsLog->vch_Token_No = $tokenNo;
                        $ifmsLog->dtm_Token_Date = $tokenDate;
                        $ifmsLog->vch_sanction_order_no = $value['vch_sanction_order_no'];
                        $ifmsLog->int_Status = 1;
                        $ifmsLog->save();

                        $application = DB::table('t_application')->where('vch_Approval_Order_No', $value['vch_sanction_order_no'])->update(['vch_sanction_no' => $sanctionNo, 'dte_Sanction_Date' => $sanctionDate]);
                
                        Storage::disk('ifms')->move('RCV/RCV'.$fileName.'.zip', 'RCV/DONE/RCV'.$fileName.'.zip');
			            echo 'rcv';
                    }
                    continue;
                }
 
            }

        } catch (\Exception $e) {
          $error = $e->getMessage();  

        }
    }

    
    public function signature($file, $cert){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "http://10.1.1.71:8080/osspService/rest/signatureGenerate", // 164.164.122.165      //10.1.1.71
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => array('pfxFile'=> new \CURLFILE(storage_path().'/app/public/Live_Certificate/'.$cert),'pfxPassword' => 'Uidai@123','xmlFile'=> new \CURLFILE(storage_path().'/app/public/'.$file.'.xml')),
        ));

        $response = json_decode(curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        // dd($response);
        return $response->response;
    }

    public function getfile($filepath, $extension)
    {
       
        $mimetype = Storage::disk('sftp')->mimeType($filepath . '.' . $extension);
        //  echo $filepath . '.' . $extension;die;
        if (in_array($mimetype, ['image/jpeg', 'image/jpeg', 'image/png', 'image/gif'])) {
            $img = Image::make(Storage::disk('sftp')->get($filepath . '.' . $extension));
            return $img->response($extension);
        } elseif ($mimetype = 'application/pdf') {
            // return Storage::disk('sftp')->download($filepath . '.' . $extension);
            return Response::make(Storage::disk('sftp')->get($filepath . '.' . $extension), 200)->header('Content-Type', $mimetype);
        } elseif ($mimetype = 'video/mp4') {
            return Response::make(Storage::disk('sftp')->get($filepath . '.' . $extension), 200)->header('Content-Type', "video/mp4");
        } elseif ($mimetype = 'audio/mpeg' || $mimetype = 'audio/wav') {
            return Response::make(Storage::disk('sftp')->get($filepath . '.' . $extension), 200)->header('Content-Type', $mimetype);
        } else {
            return 'Invalid File type';
        }
    }
}// end Class
