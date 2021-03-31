<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Image;
use App\Models\ImageThumb;
use App\Models\Log;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $imgs = [];
        $images = Image::all();
        foreach($images as $image) {
            $thumbs = [];
            foreach($image->thumbs as $thumb) {
                $thumb['path'] = asset('images/thumbs/' . $thumb['path']);
            }
            $image['path'] = asset('images/' . $image['path']);
            $image['thumbs'] = $thumbs;
            $imgs[] = $image;
        }
        return response([ 'images' => ProjectResource::collection($imgs), 'message' => 'Retrieved successfully'], 200);
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
        $imageUrls = [];
        try {
            if (!$request->hasFile('fileNames')) {
                Log::error(400, 'upload_file_not_found');
                return response()->json([
                    'message' => 'upload_file_not_found'
                ], 400);
            }

            $allowedfileExtension = ['pdf', 'jpg', 'jpeg', 'png'];
            $files = $request->file('fileNames');

            foreach ($files as $file) {
                $extension = $file->getClientOriginalExtension();
                $check = in_array($extension, $allowedfileExtension);
                if ($check) {
                    foreach ($request->fileNames as $mediaFiles) {
                        $fileName = uniqid() . '.' . $extension;
                        $mediaFiles->storeAs('public/images', $fileName);
                        $imageUrls[] = $this->processImages($fileName, $extension);
                    }
                } else {
                    Log::error(422, 'invalid_file_format');
                    return response()->json([
                        'message' => 'invalid_file_format'
                    ], 422);
                }
                return response()->json([
                    'message' => 'file_uploaded',
                    'images'  => $imageUrls
                ], 200);
            }
        } catch(Exception $ex) {
            Log::error(500, $ex->getMessage());
            return response()->json(['internal_error'], 500);
        }
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
        $imageUrls = [];
        try {
            if (!$request->has('imagesBase64')) {
                Log::error(400, 'base64_not_found');
                return response()->json([
                    'message' => 'base64_not_found'
                ], 400);
            }

            foreach ($request->post('imagesBase64') as $img) {
                $image_parts = explode(";base64,", $img);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];
                $image_base64 = base64_decode($image_parts[1]);

                $fileName = uniqid() . '.' . $image_type;
                $file = $this->folderPath . $fileName;

                file_put_contents(storage_path($file), $image_base64);

                $imageUrls[] = $this->processImages($fileName, $image_type);
            }

            return response()->json([
                'message' => 'base64_uploaded',
                'images'  => $imageUrls
            ], 200);
        } catch (Exception $ex) {
            Log::error(500, $ex->getMessage());
            return response()->json([
                'message' => 'internal_error'
            ], 500);
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
        $imageUrls = [];
        $validator = Validator::make(request()->all(), [
            'urls' => 'required|array|email',
        ], [
            'urls' => 'Invalid url'
        ]);

        if ($validator->fails()) {
            Log::error(422, '$validator->messages()');
            return response()->json([
                'message' => $validator->messages()
            ], 422);
        }
        foreach ($request->input('urls') as $url) {
            try {
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
            } catch (Exception $ex) {
                Log::error(500, $ex->getMessage());
                return response()->json([
                    'message' => $ex->getMessage()
                ], 500);
            }

            $imageUrls[] = $this->processImages($fileName, $ext);
        }
        return response()->json([
            'message' => 'base64_uploaded',
            'images'  => $imageUrls
        ], 200);
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

    private function processImages($file, $ext) {
        $images = [];
        try {
            $manager = new ImageManager(array('driver' => 'imagick'));
            $origImg = $manager->make(storage_path($this->folderPath . $file));
            $width = $origImg->getWidth();
            $height = $origImg->getHeight();

            $imageManager = $origImg->resize(100, 100, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->resizeCanvas(100, 100);
            $thumb100Name = uniqid() . '_100x100.' . $ext;
            $thumb100Path = storage_path($this->folderPath) . 'thumbs/' . $thumb100Name;
            $imageManager->save($thumb100Path);


            $origImg = $manager->make(storage_path($this->folderPath . $file));
            $imageManager = $origImg->resize(400, 400, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->resizeCanvas(400, 400);
            $thumb400Name = uniqid() . '_400x400.' . $ext;
            $thumb400Path = storage_path($this->folderPath) . 'thumbs/' . $thumb400Name;
            $imageManager->save($thumb400Path);
        } catch(Exception $ex) {
            Log::error(500, $ex->getMessage());
            return response()->json([$ex->getMessage()], 500);
        }

        $images[] = [
            'original' => asset('images/' . $file),
            'thumbs' => [
                '100x100' => asset('images/thumbs/' . $thumb100Name),
                '400x400' => asset('images/thumbs/' . $thumb400Name),
            ],
        ];

        DB::beginTransaction();
        try {
            //store image file into db
            $image = new Image();
            $image->path = $file;
            $image->width = $width;
            $image->height = $height;
            $image->save();

            //store image thumb file into db
            $saveThumb = new ImageThumb();
            $saveThumb->image_id = $image->id;
            $saveThumb->path = $thumb100Name;
            $saveThumb->width = 100;
            $saveThumb->height = 100;
            $saveThumb->save();

            //store image thumb file into db
            $saveThumb = new ImageThumb();
            $saveThumb->image_id = $image->id;
            $saveThumb->path = $thumb400Name;
            $saveThumb->width = 400;
            $saveThumb->height = 400;
            $saveThumb->save();

            DB::commit();
        } catch(Exception $ex) {
            DB::rollback();
        }

        return $images;
    }
}
