<?php

namespace Oneup\UploaderBundle\Controller;

use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oneup\UploaderBundle\Controller\AbstractChunkedController;
use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;

class PluploadController extends AbstractChunkedController
{
    public function upload()
    {
        $request = $this->container->get('request');
        $response = new EmptyResponse();
        $files = $request->files;
        
        $chunked = !is_null($request->get('chunks'));
        
        foreach($files as $file)
        {
            try
            {
                $uploaded = $chunked ? $this->handleChunkedUpload($file) : $this->handleUpload($file);
                
                // dispatch POST_PERSIST AND POST_UPLOAD events
                $this->dispatchEvents($uploaded, $response, $request);
            }
            catch(UploadException $e)
            {
                // return nothing
                return new JsonResponse(array());
            }
        }
        
        return new JsonResponse($response->assemble());
    }
    
    protected function parseChunkedRequest(Request $request)
    {
        $session = $this->container->get('session');
        
        $orig  = $request->get('name');
        $index = $request->get('chunk');
        $last  = $request->get('chunks') - 1 == $request->get('chunk');
        
        // it is possible, that two clients send a file with the
        // exact same filename, therefore we have to add the session
        // to the uuid otherwise we will get a mess
        $uuid = md5(sprintf('%s.%s', $orig, $session->getId()));
        
        return array($last, $uuid, $index, $orig);
    }
}