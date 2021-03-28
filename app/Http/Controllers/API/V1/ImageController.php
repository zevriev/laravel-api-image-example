<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Image;
use App\Models\ImageThumb;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;

class ImageController extends Controller
{
    public $folderPath = "app/public/images/";

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
    public function storeBase64(Request $request)
    {
        foreach ($request->post('imagesBase64') as $img) {
            $image_parts = explode(";base64,", $img);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);

            $fileName = uniqid() . '.' . $image_type;
            $file = $this->folderPath . $fileName;

            file_put_contents(storage_path($file), $image_base64);

            //store image file into directory and db
            $save = new Image();
            $save->title = $fileName;
            $save->path = $this->folderPath . $fileName;
            $save->save();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     *    @OA\Post(
     *      path="/imagesFromUrl",
     *      operationId="imagesFromUrl",
     *      tags={"Image"},
     *      summary="Save images",
     *      description="Save images",
     *      @OA\Parameter(
     *         name="urls[]",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="array",
     *           @OA\Items(type="string"),
     *         )
     *     ),
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
    public function imagesFromUrl(Request $request) {
        foreach ($request->input('urls') as $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            $raw = curl_exec($ch);
            list($type, $ext) = explode('/', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));

            $fileName = uniqid() . '.' . $ext;
            $file = $this->folderPath . $fileName;
            if (file_exists($file)) {
                unlink($file);
            }
            $fp = fopen(storage_path($file), 'x');
            fwrite($fp, $raw);
            fclose($fp);
            curl_close($ch);

            //store image file into db
            $image = new Image();
            $image->title = $fileName;
            $image->path = $file;
            $image->save();


            $manager = new ImageManager(array('driver' => 'imagick'));
            $imageManager = $manager->make(storage_path($file))->resize(100, 100, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->resizeCanvas(100, 100);
            $thumb100Path = storage_path($this->folderPath) . 'thumbs/' . uniqid() . '_100x100' . $ext;
            $imageManager->save($thumb100Path);

            //store image thumb file into db
            $saveThumb = new ImageThumb();
            $saveThumb->image_id = $image->id;
            $saveThumb->path = $thumb100Path;
            $saveThumb->width = 100;
            $saveThumb->height = 100;
            $saveThumb->save();


            $imageManager = $manager->make(storage_path($file))->resize(400, 400, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->resizeCanvas(400, 400);
            $thumb400Path = storage_path($this->folderPath) . 'thumbs/' . uniqid() . '_400x400' . $ext;
            $imageManager->save($thumb400Path);

            //store image thumb file into db
            $saveThumb = new ImageThumb();
            $saveThumb->image_id = $image->id;
            $saveThumb->path = $thumb400Path;
            $saveThumb->width = 400;
            $saveThumb->height = 400;
            $saveThumb->save();
        }
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
