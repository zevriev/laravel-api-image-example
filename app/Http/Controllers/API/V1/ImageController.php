<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Image;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     *
     *
     * @OA\Get(
     *      path="/images",
     *      operationId="getImagesList",
     *      tags={"Image"},
     *      summary="Get list of images",
     *      description="Returns list of images",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ProjectResource")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function index()
    {
        $images = Image::all();
        return response([ 'images' => ProjectResource::collection($images), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     *  * @OA\Post(
     *      path="/images",
     *      operationId="store",
     *      tags={"Image"},
     *      summary="Save images",
     *      description="Save images",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="fileNames[]",
     *                      description="fileNames",
     *                      type="array",
     *                      @OA\Items(type="file", format="binary")
     *                   ),
     *               ),
     *           ),
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ProjectResource")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function store(Request $request)
    {
        if(!$request->hasFile('fileNames')) {
            return response()->json(['upload_file_not_found'], 400);
        }

        $allowedfileExtension=['pdf','jpg', 'jpeg','png'];
        $files = $request->file('fileNames');
        $errors = [];

        foreach ($files as $file) {

            $extension = $file->getClientOriginalExtension();

            $check = in_array($extension,$allowedfileExtension);

            if($check) {
                foreach($request->fileNames as $mediaFiles) {

                    $path = $mediaFiles->store('public/images');
                    $name = $mediaFiles->getClientOriginalName();

                    //store image file into directory and db
                    $save = new Image();
                    $save->title = $name;
                    $save->path = $path;
                    $save->save();
                }
            } else {
                return response()->json(['invalid_file_format'], 422);
            }

            return response()->json(['file_uploaded'], 200);

        }

//        if ($validator->fails()) {
//            return response()->json(['message' => $validator->messages()->first()], 500);
//        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     *    @OA\Post(
     *      path="/imagesBase64",
     *      operationId="imagesBase64",
     *      tags={"Image"},
     *      summary="Save images",
     *      description="Save images",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="imagesBase64[]",
     *                      type="array",
     *                      @OA\Items(type="string", format="string")
     *                   ),
     *               ),
     *           ),
     *       ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ProjectResource")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *    )
     */
    private function storeBase64(Request $request)
    {
        print_r($request->base64image->count());
        $img = '';
        $folderPath = "images/";

        $image_parts = explode(";base64,", $img);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . uniqid() . '. '.$image_type;

        file_put_contents($file, $image_base64);

    }

    private function downloadImageFromUrl($url, $saveto) {
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $raw = curl_exec($ch);
        curl_close ($ch);
        if(file_exists($saveto)){
            unlink($saveto);
        }
        $fp = fopen($saveto,'x');
        fwrite($fp, $raw);
        fclose($fp);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *      path="/images/{id}",
     *      operationId="getImageById",
     *      tags={"Image"},
     *      summary="Get image information",
     *      description="Returns image data",
     *      @OA\Parameter(
     *          name="id",
     *          description="image id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Image")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function show(Image $image)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     *
     * * @OA\Delete(
     *      path="/images/{id}",
     *      operationId="deleteImage",
     *      tags={"Image"},
     *      summary="Delete existing image",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Image id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function destroy(Image $image)
    {
        $image->delete();

        return response(['message' => 'Deleted']);
    }
}
