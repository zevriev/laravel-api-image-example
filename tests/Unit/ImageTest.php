<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageTest extends TestCase
{
    /**
     * An image list test.
     *
     * @return void
     */
    public function testIndex() {
        $response = $this->get('/api/v1/images');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Retrieved successfully',
                ]);
    }

    /**
     * An image upload multipart test
     * @return void
     */
    public function testStore() {
        Storage::fake('images');

        $response = $this->json('POST', 'api/v1/images', [
            'fileNames' => [UploadedFile::fake()->image('photo1.jpg', 600, 600),
                            UploadedFile::fake()->image('photo2.jpg', 1000, 1000)]
        ])->assertStatus(200)->decodeResponseJson();

        $imageOriginal1Path = $response['images'][0]['original']['path'];
        $imageOriginal1Thumb100 = $response['images'][0]['thumbs']['100x100']['path'];
        $imageOriginal1Thumb400 = $response['images'][0]['thumbs']['400x400']['path'];

        $imageOriginal2Path = $response['images'][1]['original']['path'];
        $imageOriginal2Thumb100 = $response['images'][1]['thumbs']['100x100']['path'];
        $imageOriginal2Thumb400 = $response['images'][1]['thumbs']['400x400']['path'];
dd($this->get('/images/60647991540d2.jpeg')->assertOk());
        $this->get($imageOriginal1Path)->assertRedirect();
        $this->get($imageOriginal1Thumb100)->assertStatus(200);
        $this->get($imageOriginal1Thumb400)->assertStatus(200);

        $this->get($imageOriginal2Path)->assertStatus(200);
        $this->get($imageOriginal2Thumb100)->assertStatus(200);
        $this->get($imageOriginal2Thumb400)->assertStatus(200);

    }

    /**
     * An image save from base64 test
     * @return void
     */
    public function storeBase64Test() {

    }

    /**
     * An image download from url test
     * @return void
     */
    public function imagesFromUrlTest() {

    }

    /**
     * An image show test
     * @return void
     */
    public function showTest() {

    }

    /**
     * An image deleting test
     * @return void
     */
    public function destroyTest() {

    }
}
