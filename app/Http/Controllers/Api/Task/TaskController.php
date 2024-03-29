<?php
namespace App\Http\Controllers\Api\Task;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task\Task AS RecordModel;
use Illuminate\Http\File;
use App\Http\Controllers\CrudController;

class TaskController extends Controller
{
    private $selectedFields = ['id', 'name','duration', 'start', 'end', 'amount', 'amount_type','created_at','created_by','status','exchange_rate'];
    /** Get a list of Archives */
    public function index(Request $request){

        /** Format from query string */
        $search = isset( $request->search ) && $request->serach !== "" ? $request->search : false ;
        $perPage = isset( $request->perPage ) && $request->perPage !== "" ? $request->perPage : 50 ;
        $page = isset( $request->page ) && $request->page !== "" ? $request->page : 1 ;
        // $number = isset( $request->number ) && $request->number !== "" ? $request->number : false ;
        // $type = isset( $request->type ) && $request->type !== "" ? $request->type : false ;
        // $unit = isset( $request->unit ) && $request->unit !== "" ? $request->unit : false ;
        // $date = isset( $request->date ) && $request->date !== "" ? $request->date : false ;


        $queryString = [
            // "where" => [
            //     'default' => [
            //         [
            //             'field' => 'type_id' ,
            //             'value' => $type === false ? "" : $type
            //         ]
            //     ],
            //     'in' => [] ,
            //     'not' => [] ,
            //     'like' => [
            //         [
            //             'field' => 'number' ,
            //             'value' => $number === false ? "" : $number
            //         ],
            //         [
            //             'field' => 'year' ,
            //             'value' => $date === false ? "" : $date
            //         ]
            //     ] ,
            // ] ,
            // "pivots" => [
            //     $unit ?
            //     [
            //         "relationship" => 'units',
            //         "where" => [
            //             "in" => [
            //                 "field" => "id",
            //                 "value" => [$request->unit]
            //             ],
            //         // "not"=> [
            //         //     [
            //         //         "field" => 'fieldName' ,
            //         //         "value"=> 'value'
            //         //     ]
            //         // ],
            //         // "like"=>  [
            //         //     [
            //         //        "field"=> 'fieldName' ,
            //         //        "value"=> 'value'
            //         //     ]
            //         // ]
            //         ]
            //     ]
            //     : []
            // ],
            "pagination" => [
                'perPage' => $perPage,
                'page' => $page
            ],
            "search" => $search === false ? [] : [
                'value' => $search ,
                'fields' => [
                    'name', 'start' , 'end'
                ]
            ],
            "order" => [
                'field' => 'created_at' ,
                'by' => 'desc'
            ],
        ];

        $request->merge( $queryString );

        $crud = new CrudController(new RecordModel(), $request, $this->selectedFields );
        $crud->setRelationshipFunctions([
            /** relationship name => [ array of fields name to be selected ] */
            'creator' => ['id', 'firstname', 'lastname' ,'username'] 
        ]);
        $builder = $crud->getListBuilder();

        /** Filter the record by the user role */
        // if( ( $user = $request->user() ) !== null ){
        //     /** In case user is the administrator, all archives will show up */
        //     if( array_intersect( $user->roles()->pluck('id')->toArray() , [2,3,4] ) ){
        //         /** In case user is the super, auditor, member then the archives will show up if only that archives are own by them */
        //         $builder->where('created_by',$user->id);
        //     }else{
        //         /** In case user is the customer */
        //         /** Filter archives by its type before showing to customer */
        //     }
        // }

        $responseData = $crud->pagination(true, $builder);
        $responseData['message'] = __("crud.read.success");
        return response()->json($responseData, 200);
    }
    /** Create a new Archive */
    public function create(Request $request){
        if( ($user = $request->user() ) !== null ){
            $crud = new CrudController(new RecordModel(), $request, $this->selectedFields);
            
            if (($record = $crud->create([
                'name' => $request->name ,
                'duration' => $request->duration ,
                'amount' => $request->amount ,
                'amount_type' => $request->amount_type ,
                'exchange_rate' => $request->exchange_rate ,
                'created_by' => $user->id
            ])) !== false) {
                /** Link the archive to the units */
                $record = $crud->formatRecord($record);
                return response()->json([
                    'record' => $record,
                    'message' => __("crud.created.success")
                ], 200);
            }
            return response()->json([
                'record' => null,
                'message' => __("crud.created.failed")
            ], 201);
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
        
    }
    /** Updating the archive */
    public function update(Request $request)
    {
        if (($user = $request->user()) !== null) {
            $crud = new CrudController(new RecordModel(), $request, $this->selectedFields);
            $crud->setRelationshipFunctions([
                'units' => false
            ]);
            if ( $crud->update([
                'name' => $request->name ,
                'duration' => $request->duration ,
                'amount' => $request->amount ,
                'amount_type' => $request->amount_type ,
                'exchange_rate' => $request->exchange_rate ,
                'updated_by' => $user->id
            ]) !== false) {
                $record = $crud->read();
                $record = $crud->formatRecord($record);
                return response()->json([
                    'ok' => true ,
                    'record' => $record,
                    'message' => __("crud.update.success")
                ], 200);
            }
            return response()->json([
                'ok' => false ,
                'record' => null,
                'message' => __("crud.update.failed")
            ], 201);
        }
        return response()->json([
            'ok' => false ,
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
    /** Updating the archive */
    public function read(Request $request)
    {
        if (($user = $request->user()) !== null) {
            $crud = new CrudController(new RecordModel(), $request, $this->selectedFields);
            // $crud->setRelationshipFunctions([
            //     'units' => false
            // ]);
            if (($record = $crud->read()) !== false) {
                $record = $crud->formatRecord($record);
                return response()->json([
                    'record' => $record,
                    'message' => __("crud.read.success")
                ], 200);
            }
            return response()->json([
                'record' => null,
                'message' => __("crud.read.failed")
            ], 201);
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
    /** Reading an archive */
    public function delete(Request $request)
    {
        if (($user = $request->user()) !== null) {
            /** Merge variable created_by and updated_by into request */
            // $input = $request->input();
            // $input['updated_at'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            // $input['updated_by'] = $user->id;
            // $request->merge($input);

            $crud = new CrudController(new RecordModel(), $request, $this->selectedFields);
            if (($record = $crud->delete()) !== false) {
                /** Delete its structure and matras too */
                return response()->json([
                    'record' => $record,
                    'message' => __("crud.delete.success")
                ], 200);
            }
            return response()->json([
                'record' => null,
                'message' => __("crud.delete.failed")
            ], 201);
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
    /** Upload file */
    public function upload(Request $request){
        if (($user = $request->user()) !== null) {
            $crud = new CrudController(new RecordModel(), $request, $this->selectedFields);
            $record = $crud->read();
            list($year,$month,$day) = explode('-', \Carbon\Carbon::parse( $record->year )->format('Y-m-d') );
            $path = $record->type_id."/".$year;
            if (($record = $crud->upload('pdfs',$path, new File($_FILES['files']['tmp_name'][0]),$record->type_id.'-'.$year.$month.$day."-".$record->number.'.pdf' )) !== false) {
                // $record = $crud->formatRecord($record);
                return response()->json([
                    'record' => $record,
                    'message' => __("crud.delete.success")
                ], 200);
            }
            return response()->json([
                'record' => null,
                'message' => __("crud.delete.failed")
            ], 201);
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
    /** Active the record */
    public function active(Request $request)
    {
        if (($user = $request->user()) !== null) {
            $crud = new CrudController(new RecordModel(), $request, $this->selectedFields);
            if ($crud->booleanField('active', 1)) {
                $record = $crud->formatRecord($record = $crud->read());
                return response(
                    [
                        'record' => $record,
                        'message' => 'Activated !'
                    ],
                    200
                );
            } else {
                return response(
                    [
                        'record' => null,
                        'message' => 'There is not record matched !'
                    ],
                    350
                );
            }
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
    /** Unactive the record */
    public function unactive(Request $request)
    {
        if (($user = $request->user()) !== null) {
            $crud = new CrudController(new RecordModel(), $request, $this->selectedFields);
            if ( $crud->booleanField('active', 0) ) {
                // User does exists
                return response(
                    [
                        'record' => $record,
                        'message' => 'Deactivated !'
                    ],
                    200
                );
            } else {
                return response(
                    [
                        'record' => null,
                        'message' => 'There is not record matched !'
                    ],
                    350
                );
            }
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
    /**
     * Remove file
     */
    public function removefile(Request $request)
    {
        $crud = new CrudController(new RecordModel(), $request, $this->selectedFields);
        if (($record = $crud->removeFile('pdfs')) != null) {
            $record = $crud->formatRecord( $record );
            return response()->json([
                'record' => $record ,
                'message' => __('crud.remove.file.success')
            ], 200);
        }
        return response()->json([
            'message' => __('crud.remove.file.success')
        ], 350);
    }
    /** Mini display */
    public function forFilter(Request $request)
    {
        $crud = new CrudController(new RecordModel(), $request, $this->selectedFields);
        $responseData['records'] = $crud->getListBuilder()->where('active', 1)->limit(10)->get();;
        $responseData['message'] = __("crud.read.success");
        return response()->json($responseData, 200);
    }
    public function startTask(Request $request){
        if (($user = $request->user()) !== null) {
            $record = RecordModel::find( $request->id );
            if( $record == null ){
                return response(
                    [
                        'ok' => false ,
                        'record' => null,
                        'message' => 'សូមបញ្ជាក់លេខសម្គាល់របស់ការងារដែលអ្នកចង់ចាប់ផ្ដើម។'
                    ],
                    350
                );
            }
            if( $record->markAsStart() ){
                return response(
                    [
                        'ok' => true ,
                        'record' => $record,
                        'message' => 'ការងារបានចាប់ផ្ដើមរួចរាល់។'
                    ],
                    200
                );
            }else{
                return response(
                    [
                        'ok' => false ,
                        'record' => null,
                        'message' => 'មានបញ្ហាក្នុងការចាប់ផ្ដើមការងារ។'
                    ],
                    350
                );
            }
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
    public function continueTask(Request $request){
        if (($user = $request->user()) !== null) {
            $record = RecordModel::find( $request->id );
            if( $record == null ){
                return response(
                    [
                        'ok' => false ,
                        'record' => null,
                        'message' => 'សូមបញ្ជាក់លេខសម្គាល់របស់ការងារដែលអ្នកចង់ចាប់ផ្ដើម។'
                    ],
                    350
                );
            }
            if( $record->markAsContinue() ){
                return response(
                    [
                        'ok' => true ,
                        'record' => $record,
                        'message' => 'ចាប់ផ្ដើមបន្តការងាររួចរាល់។'
                    ],
                    200
                );
            }else{
                return response(
                    [
                        'ok' => false ,
                        'record' => null,
                        'message' => 'មានបញ្ហាក្នុងការ ចាប់ផ្ដើមបន្តការងារ។'
                    ],
                    350
                );
            }
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
    public function pendingTask(Request $request){
        if (($user = $request->user()) !== null) {
            $record = RecordModel::find( $request->id );
            if( $record == null ){
                return response(
                    [
                        'ok' => false ,
                        'record' => null,
                        'message' => 'សូមបញ្ជាក់លេខសម្គាល់របស់ការងារដែលអ្នកចង់ចាប់ផ្ដើម។'
                    ],
                    350
                );
            }
            if( $record->markAsPending() ){
                return response(
                    [
                        'ok' => true ,
                        'record' => $record,
                        'message' => 'ដាក់ពន្យាពេលការងាររួចរាល់។'
                    ],
                    200
                );
            }else{
                return response(
                    [
                        'ok' => false ,
                        'record' => null,
                        'message' => 'មានបញ្ហាក្នុងការ ដាក់ពន្យាពេលការងារ'
                    ],
                    350
                );
            }
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
    public function endTask(Request $request){
        if (($user = $request->user()) !== null) {
            $record = RecordModel::find( $request->id );
            if( $record == null ){
                return response(
                    [
                        'ok' => false ,
                        'record' => null,
                        'message' => 'សូមបញ្ជាក់លេខសម្គាល់របស់ការងារដែលអ្នកចង់ចាប់ផ្ដើម។'
                    ],
                    350
                );
            }
            if( $record->markAsEnd() ){
                return response(
                    [
                        'ok' => true ,
                        'record' => $record,
                        'message' => 'បញ្ចាប់ការងារ។'
                    ],
                    200
                );
            }else{
                return response(
                    [
                        'ok' => false ,
                        'record' => null,
                        'message' => 'មានបញ្ហាក្នុងការ បញ្ចប់ការងារ'
                    ],
                    350
                );
            }
        }
        return response()->json([
            'record' => null,
            'message' => __("crud.auth.failed")
        ], 401);
    }
}
