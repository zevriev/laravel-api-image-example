<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageTest extends TestCase
{
    /**
     * An image list test.
     *
     * @return void
     */
    public function indexTest() {
        $response = $this->get('/api/images');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Retrieved successfully',
                ]);
    }

    /**
     * An image upload multipart test
     * @return void
     */
    public function storeTest() {

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
