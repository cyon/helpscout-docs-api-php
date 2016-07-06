<?php

namespace HelpScoutDocs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use HelpScoutDocs\Models\Article;
use HelpScoutDocs\Models\ArticleAsset;
use HelpScoutDocs\Models\ArticleRef;
use HelpScoutDocs\Models\ArticleRevision;
use HelpScoutDocs\Models\ArticleRevisionRef;
use HelpScoutDocs\Models\ArticleSearch;
use HelpScoutDocs\Models\Category;
use HelpScoutDocs\Models\SettingsAsset;
use HelpScoutDocs\Models\Site;
use HelpScoutDocs\Models\Collection;
use HelpScoutDocs\Models\UploadArticle;

/**
 * Class DocsApiClient
 *
 * This class is partially replicated from ApiClient
 * https://github.com/helpscout/helpscout-api-php
 *
 * @package HelpScoutDocs
 */
class DocsApiClient {
    
    const USER_AGENT = 'Help Scout API/Php Client v1';
    const API_URL = 'https://docsapi.helpscout.net/v1/';
    
    private $userAgent = false;
    private $apiKey    = false;
    private $isDebug   = false;
    private $debugDir  = false;
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client();
    }
    /**
     * Put ApiClient in debug mode or note.
     *
     * If in debug mode, you can optionally supply a directory
     * in which to write debug messages.
     * If no directory is set, debug messages are echo'ed out.
     *
     * @param  boolean        $bool
     * @param  boolean|string $dir
     * @return void
     */
    public function setDebug($bool, $dir = false)
    {
        $this->isDebug = $bool;
        if ($dir && is_dir($dir)) {
            $this->debugDir = $dir;
        }
    }

    /**
     * Set the API Key to use with this request
     *
     * @param string $apiKey
     */
    public function setKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setUserAgent($userAgent)
    {
        $userAgent = trim($userAgent);
        if (!empty($userAgent)) {
            $this->userAgent = $userAgent;
        }
    }

    private function getUserAgent()
    {
        if ($this->userAgent) {
            return $this->userAgent;
        }
        return self::USER_AGENT;
    }

    public function setHttpClient($client)
    {
        $this->httpClient = $client;
    }

    /**
     * @param string $url
     * @param string $params
     * @param string $modelClass
     * @return ResourceCollection|mixed
     * @throws ApiException
     */
    private function getResourceCollection($url, $params, $modelClass)
    {
        list($statusCode, $json) = $this->doGet($url, $params);

        $this->checkStatus($statusCode, 'GET');

        $json = json_decode($json);
        $json = reset($json);
        
        if ($json) {
            if (isset($params['fields'])) {
                return $json;
            } else {
                return new ResourceCollection($json, $modelClass);
            }
        }
        return false;
    }

    /**
     * @param $url
     * @param $params
     * @param $modelClass
     * @return bool|mixed
     * @throws ApiException
     */
    private function getItem($url, $params, $modelClass)
    {
        list($statusCode, $json) = $this->doGet($url, $params);
        $this->checkStatus($statusCode, 'GET');

        $json = json_decode($json);
        $json = reset($json);
        if ($json) {
            if (isset($params['fields']) || !$modelClass) {
                return $json;
            } else {
                return new $modelClass($json);
            }
        }
        return false;
    }

    /**
     * @param  integer $statusCode The HTTP status code returned
     * @param  string  $type       The type of request (e.g., GET, POST, etc.)
     * @param  integer $expected   The expected HTTP status code
     * @return void
     * @throws \HelpScoutDocs\ApiException If the expected $statusCode isn't returned
     */
    private function checkStatus($statusCode, $type, $expected = 200)
    {
        if (!is_array($expected)) {
            $expected = array($expected);
        }

        if (!in_array($statusCode, $expected)) {
            switch($statusCode) {
                case 400:
                    throw new ApiException('The request was not formatted correctly', 400);
                    break;
                case 401:
                    throw new ApiException('Invalid API key', 401);
                    break;
                case 402:
                    throw new ApiException('API key suspended', 402);
                    break;
                case 403:
                    throw new ApiException('Access denied', 403);
                    break;
                case 404:
                    throw new ApiException(sprintf('Resource not found [%s]', $type), 404);
                    break;
                case 405:
                    throw new ApiException('Invalid method type', 405);
                    break;
                case 429:
                    throw new ApiException('Throttle limit reached. Too many requests', 429);
                    break;
                case 500:
                    throw new ApiException('Application error or server error', 500);
                    break;
                case 503:
                    throw new ApiException('Service Temporarily Unavailable', 503);
                    break;
                default:
                    throw new ApiException(sprintf(
                        'Method %s returned status code %d but we expected code(s) %s',
                        $type,
                        $statusCode,
                        implode(',', $expected)
                    ));
                    break;
            }
        }
    }

    /**
     * @param array $params
     * @param array $accepted
     * @return array
     */
    private function getParams(array $params = [], array $accepted = ['page', 'sort', 'order', 'status', 'query'])
    {
        if (!$params) {
            return array();
        }
        foreach($params as $key => $val) {
            $key = trim($key);
            if (!in_array($key, $accepted) || empty($params[$key])) {
                unset($params[$key]);
                continue;
            }
            switch($key) {
                case 'fields':
                    $val = $this->validateFieldSelectors($val);
                    if (empty($val)) {
                        unset($params[$key]);
                    } else {
                        $params[$key] = $val;
                    }
                    break;
                case 'page':
                    $val = intval($val);
                    if ($val < 1) {
                        unset($params[$key]);
                    }
                    break;
                case 'sort':
                    $params[$key] = $val;
                    break;
                case 'order':
                    $params[$key] = $val;
                    break;
                case 'visibility':
                    $params[$key] = $val;
                    break;
                case 'status':
                    $params[$key] = $val;
                    break;
            }
        }
        if ($params) {
            return $params;
        }
        return array();
    }

    /**
     * @param array $params
     * @return array
     */
    private function prepareParams(array $params)
    {
        foreach($params as $key => $value) {
            if (empty($value)) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * @param  string|array $fields
     * @return string
     */
    private function validateFieldSelectors($fields)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        if (is_array($fields) && count($fields) > 0) {
            array_walk($fields, create_function('&$val', '$val = trim($val);'));

            $fields = array_filter($fields);
        }

        if ($fields) {
            return implode(',', $fields);
        }
        return $fields;
    }

    /**
     * @param string $url
     * @param array $requestBody
     * @param integer $expectedCode
     * @return array
     * @throws ApiException
     */
    private function doPost($url, array $requestBody, $expectedCode)
    {
        if ($this->apiKey === false || empty($this->apiKey)) {
            throw new ApiException('Invalid API Key', 401);
        }

        if ($this->isDebug) {
            $this->debug(json_encode($requestBody));
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL . $url, [
                'json' => $requestBody,
                'auth' => [$this->apiKey, 'X']
            ]);
        } catch (ClientException $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }

        $content = $response->getBody()->getContents();
        $statusCode = $response->getStatusCode();

        $this->checkStatus($statusCode, 'POST', $expectedCode);

        $location = $response->getHeaderLine('Location');

        return array(basename($location), json_decode($content));
    }

    /**
     * @param string $url
     * @param array $requestBody
     * @param integer $expectedCode
     * @return mixed
     * @throws ApiException
     */
    private function doPut($url, array $requestBody, $expectedCode)
    {
        if ($this->apiKey === false || empty($this->apiKey)) {
            throw new ApiException('Invalid API Key', 401);
        }
        
        if ($this->isDebug) {
            $this->debug(json_encode($requestBody));
        }

        try {
            $response = $this->httpClient->request('PUT', self::API_URL . $url, [
                'json' => $requestBody,
                'auth' => [$this->apiKey, 'X']
            ]);
        } catch (ClientException $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }

        $content = $response->getBody()->getContents();
        $statusCode = $response->getStatusCode();
        
        $this->checkStatus($statusCode, 'PUT', $expectedCode);
        
        return json_decode($content);
    }

    /**
     * @param  string $url
     * @param  integer $expectedCode
     * @throws ApiException
     * @return void
     */
    private function doDelete($url, $expectedCode)
    {
        if ($this->apiKey === false || empty($this->apiKey)) {
            throw new ApiException('Invalid API Key', 401);
        }

        if ($this->isDebug) {
            $this->debug($url);
        }

        try {
            $response = $this->httpClient->request('DELETE', self::API_URL . $url, [
                'auth' => [$this->apiKey, 'X']
            ]);
        } catch (ClientException $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }

        $statusCode = $response->getStatusCode();

        $this->checkStatus($statusCode, 'DELETE', $expectedCode);
    }

    /**
     * @param $url
     * @param array $params
     * @return array
     * @throws ApiException
     */
    private function doGet($url, array $params)
    {
        if ($this->apiKey === false || empty($this->apiKey)) {
            throw new ApiException('Invalid API Key', 401);
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL . $url, [
                'query' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'auth' => [$this->apiKey, 'X']
            ]);
        } catch(ClientException $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }

        $content = $response->getBody()->getContents();
        $statusCode = $response->getStatusCode();

        return array($statusCode, $content);
    }

    /**
     * @param $message
     */
    private function debug($message)
    {
        $text = strftime('%b %d %H:%M:%S') . ': ' . $message . PHP_EOL;

        if ($this->debugDir) {
            file_put_contents($this->debugDir . DIRECTORY_SEPARATOR . 'apiclient.log', $text, FILE_APPEND);
        } else {
            echo $text;
        }
    }

    /**
     * @param int $page
     * @param string $siteId
     * @param string $visibility
     * @param string $sort
     * @param string $order
     * @return bool|Collection
     */
    public function getCollections($page = 1, $siteId = '', $visibility = 'all', $sort = 'order', $order = 'asc')
    {
        $params = array(
            'page'       => $page,
            'siteId'     => $siteId,
            'visibility' => $visibility,
            'sort'       => $sort,
            'order'      => $order
        );

        return $this->getResourceCollection(
            "collections",
            $this->getParams($params),
            Collection::class
        );
    }

    /**
     * @param $collectionId
     * @param int $page
     * @param string $sort
     * @param string $order
     * @return bool|Collection
     */
    public function getCategories($collectionId, $page = 1, $sort = 'order', $order = 'asc')
    {
        $params = array(
            'page'  => $page,
            'sort'  => $sort,
            'order' => $order
        );

        return $this->getResourceCollection(
            sprintf("collections/%s/categories", $collectionId),
            $this->getParams($params),
            Category::class
        );
    }

    /**
     * @param $categoryId
     * @param int $page
     * @param string $status
     * @param string $sort
     * @param string $order
     * @return bool|Collection
     */
    public function getArticles($categoryId, $page = 1, $status = 'all', $sort = 'order', $order = 'asc')
    {
        $params = array(
            'page'   => $page,
            'status' => $status,
            'sort'   => $sort,
            'order'  => $order
        );

        return $this->getResourceCollection(
            sprintf("categories/%s/articles", $categoryId),
            $this->getParams($params),
            ArticleRef::class
        );
    }

    /**
     * @param int $page
     * @return bool|Collection
     */
    public function getSites($page = 1)
    {
        $params = array('page' => $page);

        return $this->getResourceCollection(
            "sites",
            $this->getParams($params),
            Site::class
        );
    }

    /**
     * @param $siteId
     * @return bool
     */
    public function getSite($siteId)
    {
        return $this->getItem(
            sprintf("sites/%s", $siteId),
            array(),
            Site::class
        );
    }

    /**
     * @param string $query
     * @param int $page
     * @param string $collectionId
     * @param string $status
     * @param string $visibility
     * @return bool|Collection
     */
    public function searchArticles($query = '*', $page = 1, $collectionId = '', $status = 'all', $visibility = 'all')
    {
        $params = array(
            'query'        => $query,
            'page'         => $page,
            'collectionId' => $collectionId,
            'status'       => $status,
            'visibility'   => $visibility
        );

        return $this->getResourceCollection(
            "search/articles",
            $this->getParams($params),
            ArticleSearch::class
        );
    }

    /**
     * @param $articleId
     * @param int $page
     * @param string $status
     * @param string $sort
     * @param string $order
     * @return bool|Collection
     */
    public function getRelatedArticles($articleId, $page = 1, $status = 'all', $sort = 'order', $order = 'desc')
    {
        $params = array(
            'page'   => $page,
            'status' => $status,
            'sort'   => $sort,
            'order'  => $order
        );

        return $this->getResourceCollection(
            sprintf("articles/%s/related", $articleId),
            $this->getParams($params),
            ArticleRef::class
        );
    }

    /**
     * @param $articleId
     * @param int $page
     * @return bool|Collection
     */
    public function getRevisions($articleId, $page = 1)
    {
        $params = array('page' => $page);

        return $this->getResourceCollection(
            sprintf("articles/%s/revisions", $articleId),
            $this->getParams($params),
            ArticleRevisionRef::class
        );
    }

    /**
     * @param $articleIdOrNumber
     * @param bool $draft
     * @return bool
     */
    public function getArticle($articleIdOrNumber, $draft = false)
    {
        $params = array('draft' => $draft);

        return $this->getItem(
            sprintf("articles/%s", $articleIdOrNumber),
            $this->getParams($params),
            Article::class
        );
    }

    /**
     * @param $revisionId
     * @return bool
     */
    public function getRevision($revisionId)
    {
        return $this->getItem(
            sprintf("revisions/%s", $revisionId),
            array(),
            ArticleRevision::class
        );
    }

    /**
     * @param Article $article
     * @param bool $reload
     * @return bool|Article
     * @throws ApiException
     */
    public function createArticle(Article $article, $reload = false)
    {
        $url = "articles";

        $requestBody = $article->toArray();

        if ($reload) {
            $requestBody['reload'] = true;
        }

        list($id, $response) = $this->doPost($url, $requestBody, 200);
        
        if ($reload) {
            $articleData = (array)$response;
            $articleData = reset($articleData);
            return new Article($articleData);
        } else {
            $article->setId($id);
            return $article;
        }
    }

    /**
     * @param Article $article
     * @param bool $reload
     * @return Article
     * @throws ApiException
     */
    public function updateArticle(Article $article, $reload = false)
    {
        $url = sprintf("articles/%s", $article->getId());

        $requestBody = $article->toArray();
        
        if ($reload) {
            $requestBody['reload'] = true;
        }

        $response = $this->doPut($url, $requestBody, 200);

        if ($reload) {
            $articleData = (array)$response;
            $articleData = reset($articleData);
            return new Article($articleData);
        } else {
            return $article;
        }
    }

    /**
     * @param UploadArticle $uploadArticle
     * @param bool $reload
     * @return bool|Article
     * @throws ApiException
     */
    public function uploadArticle(UploadArticle $uploadArticle, $reload = false)
    {
        if (!file_exists($uploadArticle->getFile())) {
            throw new ApiException("Unable to locate file: %s", $uploadArticle->getFile());
        }

        $multipart = [
            [
                'name' => 'key',
                'contents' => $this->apiKey
            ],
            [
                'name' => 'collectionId',
                'contents' => $uploadArticle->getCollectionId()
            ],
            [
                'name' => 'file',
                'contents' => fopen($uploadArticle->getFile(), 'r')
            ],
            [
                'name' => 'categoryId',
                'contents' => $uploadArticle->getCategoryId()
            ],
            [
                'name' => 'slug',
                'contents' => $uploadArticle->getSlug()
            ],
            [
                'name' => 'type',
                'contents' => $uploadArticle->getType()
            ],
            [
                'name' => 'reload',
                'contents' => $reload
            ]
        ];

        $response = $this->doPostMultipart("articles/upload", $multipart, $reload ? 200 : 201);

        $articleData = (array)$response;
        
        return $reload ? new Article(reset($articleData)) : true;
    }

    /**
     * @param $articleId
     * @param int $count
     */
    public function updateViewCount($articleId, $count = 1)
    {
        $this->doPut(
            sprintf("articles/%s/views", $articleId),
            ['count' => $count],
            200
        );
    }

    /**
     * @param $articleId
     */
    public function deleteArticle($articleId)
    {
        $this->doDelete(sprintf("articles/%s", $articleId), 200);
    }

    /**
     * @param $articleId
     * @param $text
     */
    public function saveArticleDraft($articleId, $text)
    {
        $this->doPut(
            sprintf("articles/%s/drafts", $articleId),
            ['text' => $text],
            200
        );
    }

    /**
     * @param $articleId
     */
    public function deleteArticleDraft($articleId)
    {
        $this->doDelete(sprintf("articles/%s/drafts", $articleId), 200);
    }

    /**
     * @param $categoryIdOrNumber
     * @return bool
     */
    public function getCategory($categoryIdOrNumber)
    {
        return $this->getItem(
            sprintf("categories/%s", $categoryIdOrNumber),
            array(),
            Category::class
        );
    }

    /**
     * @param Category $category
     * @param bool $reload
     * @return bool|Category
     * @throws ApiException
     */
    public function createCategory(Category $category, $reload = false)
    {
        $url = "categories";

        $requestBody = $category->toArray();

        if ($reload) {
            $requestBody['reload'] = true;
        }

        list($id, $response) = $this->doPost($url, $requestBody, $reload ? 200 : 201);

        if ($reload) {
            $categoryData = (array)$response;
            $categoryData = reset($categoryData);
            return new Category($categoryData);
        } else {
            $category->setId($id);
            return $category;
        }
    }

    /**
     * @param Category $category
     * @param bool $reload
     * @return Category
     * @throws ApiException
     */
    public function updateCategory(Category $category, $reload = false)
    {
        $url = sprintf("categories/%s", $category->getId());

        $requestBody = $category->toArray();
        
        if ($reload) {
            $requestBody['reload'] = true;
        }

        $response = $this->doPut($url, $requestBody, 200);

        if ($reload) {
            $categoryData = (array)$response;
            $categoryData = reset($categoryData);
            return new Category($categoryData);
        } else {
            return $category;
        }
    }

    /**
     * @param $collectionId
     * @param array $categories
     *
     * Categories should be an associative array:
     *
     * $categories = array(
     *      'categories' => array(
     *          array(
     *              'id'    => 'some-id-here',
     *              'order' => '1'
     *          ),
     *          array(
     *              'id'    => 'another-id-here',
     *              'order' => '2'
     *          )
     *          ...
     *      )
     * );
     *
     * @throws ApiException
     */
    public function updateCategoryOrder($collectionId, array $categories)
    {
        $this->doPut(
            sprintf("collections/%s/categories", $collectionId),
            $categories,
            200
        );
    }

    /**
     * @param $categoryId
     * @throws ApiException
     */
    public function deleteCategory($categoryId)
    {
        $this->doDelete(sprintf("categories/%s", $categoryId), 200);
    }

    /**
     * @param $collectionIdOrNumber
     * @return bool
     */
    public function getCollection($collectionIdOrNumber)
    {
        return $this->getItem(
            sprintf("collections/%s", $collectionIdOrNumber),
            array(),
            Collection::class
        );
    }

    /**
     * @param Collection $collection
     * @param bool $reload
     * @return bool|Collection
     * @throws ApiException
     */
    public function createCollection(Collection $collection, $reload = false)
    {
        $url = "collections";

        $requestBody = $collection->toArray();

        if ($reload) {
            $requestBody['reload'] = true;
        }

        list($id, $response) = $this->doPost($url, $requestBody, $reload ? 200 : 201);

        if ($reload) {
            $collectionData = (array)$response;
            $collectionData = reset($collectionData);
            return new Collection($collectionData);
        } else {
            $collection->setId($id);
            return $collection;
        }
    }

    /**
     * @param Collection $collection
     * @param bool $reload
     * @return Collection
     * @throws ApiException
     */
    public function updateCollection(Collection $collection, $reload = false)
    {
        $url = sprintf("collections/%s", $collection->getId());

        $requestBody = $collection->toArray();
        
        if ($reload) {
            $requestBody['reload'] = true;
        }

        $response = $this->doPut($url, $requestBody, 200);

        if ($reload) {
            $collectionData = (array)$response;
            $collectionData = reset($collectionData);
            return new Collection($collectionData);
        } else {
            return $collection;
        }
    }

    /**
     * @param $collectionId
     * @throws ApiException
     */
    public function deleteCollection($collectionId)
    {
        $this->doDelete(sprintf("collections/%s", $collectionId), 200);
    }

    /**
     * @param Site $site
     * @param bool $reload
     * @return bool|Site
     * @throws ApiException
     */
    public function createSite(Site $site, $reload = false)
    {
        $url = "sites";

        $requestBody = $site->toArray();

        if ($reload) {
            $requestBody['reload'] = true;
        }

        list($id, $response) = $this->doPost($url, $requestBody, $reload ? 200 : 201);

        if ($reload) {
            $siteData = (array)$response;
            $siteData = reset($siteData);
            return new Site($siteData);
        } else {
            $site->setId($id);
            return $site;
        }
    }

    /**
     * @param Site $site
     * @param bool $reload
     * @return Site
     * @throws ApiException
     */
    public function updateSite(Site $site, $reload = false)
    {
        $url = sprintf("sites/%s", $site->getId());

        $requestBody = $site->toArray();
        
        if ($reload) {
            $requestBody['reload'] = true;
        }

        $response = $this->doPut($url, $requestBody, 200);

        if ($reload) {
            $siteData = (array)$response;
            $siteData = reset($siteData);
            return new Site($siteData);
        } else {
            return $site;
        }
    }

    /**
     * @param $siteId
     * @throws ApiException
     */
    public function deleteSite($siteId)
    {
        $this->doDelete(sprintf("sites/%s", $siteId), 200);
    }

    /**
     * @param $url
     * @param array $multipart
     * @param $expectedCode
     * @return mixed
     * @throws ApiException
     */
    private function doPostMultipart($url, array $multipart, $expectedCode)
    {
        if ($this->apiKey === false || empty($this->apiKey)) {
            throw new ApiException('Invalid API Key', 401);
        }

        if ($this->isDebug) {
            $this->debug(json_encode($multipart));
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL . $url, [
                'multipart' => $multipart,
                'auth' => [$this->apiKey, 'X']
            ]);
        } catch(ClientException $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }

        $content = $response->getBody()->getContents();

        $this->checkStatus($response->getStatusCode(), 'POST', $expectedCode);

        return json_decode($content);
    }

    /**
     * @param ArticleAsset $articleAsset
     * @return ArticleAsset
     * @throws ApiException
     */
    public function createArticleAsset(ArticleAsset $articleAsset)
    {
        if (!file_exists($articleAsset->getFile())) {
            throw new ApiException("Unable to locate file: %s", $articleAsset->getFile());
        }

        if (empty($articleAsset->getArticleId())) {
            throw new ApiException("articleId is empty or not provided");
        }

        if (empty($articleAsset->getAssetType())) {
            throw new ApiException("assetType is empty or not provided");
        }

        $multipart = [
            [
                'name' => 'key',
                'contents' => $this->apiKey
            ],
            [
                'name' => 'articleId',
                'contents' => $articleAsset->getArticleId()
            ],
            [
                'name' => 'assetType',
                'contents' => $articleAsset->getAssetType()
            ],
            [
                'name' => 'file',
                'contents' => fopen($articleAsset->getFile(), 'r')
            ]
        ];
        
        $uploadedAsset = $this->doPostMultipart('assets/article', $multipart, 201);

        $articleAsset->setFileLink($uploadedAsset->filelink);

        return $articleAsset;
    }

    /**
     * @param SettingsAsset $settingsAsset
     * @return SettingsAsset
     * @throws ApiException
     */
    public function createSettingsAsset(SettingsAsset $settingsAsset)
    {
        if (!file_exists($settingsAsset->getFile())) {
            throw new ApiException("Unable to locate file: %s", $settingsAsset->getFile());
        }

        if (empty($settingsAsset->getAssetType())) {
            throw new ApiException("assetType is empty or not provided");
        }

        if (empty($settingsAsset->getSiteId())) {
            throw new ApiException("siteId is empty or not provided");
        }

        $multipart = [
            [
                'name' => 'key',
                'contents' => $this->apiKey
            ],
            [
                'name' => 'assetType',
                'contents' => $settingsAsset->getAssetType()
            ],
            [
                'name' => 'siteId',
                'contents' => $settingsAsset->getSiteId()
            ],
            [
                'name' => 'file',
                'contents' => fopen($settingsAsset->getFile(), 'r')
            ]
        ];

        $uploadedAsset = $this->doPostMultipart('assets/settings', $multipart, 201);

        $settingsAsset->setFileLink($uploadedAsset->filelink);

        return $settingsAsset;
    }
}