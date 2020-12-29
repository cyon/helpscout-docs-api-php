<?php

namespace HelpScoutDocs\Models;

class Category extends DocsModel {

    private $id;
    private $number;
    private $slug;
    private $collectionId;
    private $order;
    private $name;
    private $description;
    private $defaultSort;
    private $visibility;
    private $articleCount;
    private $publishedArticleCount;
    private $publicUrl;
    private $createdBy;
    private $updatedBy;
    private $createdAt;
    private $updatedAt;

    function __construct($data = null) {
        if ($data) {
            $this->id                    = isset($data->id)                    ? $data->id                    : null;
            $this->number                = isset($data->number)                ? $data->number                : null;
            $this->slug                  = isset($data->slug)                  ? $data->slug                  : null;
            $this->collectionId          = isset($data->collectionId)          ? $data->collectionId          : null;
            $this->order                 = isset($data->order)                 ? $data->order                 : null;
            $this->name                  = isset($data->name)                  ? $data->name                  : null;
            $this->description           = isset($data->description)           ? $data->description           : null;
            $this->defaultSort           = isset($data->defaultSort)           ? $data->defaultSort           : null;
            $this->visibility            = isset($data->visibility)            ? $data->visibility            : null;
            $this->articleCount          = isset($data->articleCount)          ? $data->articleCount          : null;
            $this->publishedArticleCount = isset($data->publishedArticleCount) ? $data->publishedArticleCount : null;
            $this->publicUrl             = isset($data->publicUrl)             ? $data->publicUrl             : null;
            $this->createdBy             = isset($data->createdBy)             ? $data->createdBy             : null;
            $this->updatedBy             = isset($data->updatedBy)             ? $data->updatedBy             : null;
            $this->createdAt             = isset($data->createdAt)             ? $data->createdAt             : null;
            $this->createdBy             = isset($data->updatedAt)             ? $data->updatedAt             : null;
        }
    }

    /**
     * @param mixed $collectionId
     */
    public function setCollectionId($collectionId)
    {
        $this->collectionId = $collectionId;
    }

    /**
     * @return mixed
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
      $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
      return $this->description;
    }

    /**
     * @return mixed
     */
    public function getDefaultSort()
    {
        return $this->defaultSort;
    }

    /**
     * @param mixed $defaultSort
     */
    public function setDefaultSort($defaultSort)
    {
        $this->defaultSort = $defaultSort;
    }

    /**
     * @return mixed
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param mixed $visibility
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    /**
     * @return mixed
     */
    public function getArticleCount()
    {
        return $this->articleCount;
    }

    /**
     * @param mixed $articleCount
     */
    public function setArticleCount($articleCount)
    {
        $this->articleCount = $articleCount;
    }

    /**
     * @return mixed
     */
    public function getPublishedArticleCount()
    {
        return $this->publishedArticleCount;
    }

    /**
     * @param mixed $publishedArticleCount
     */
    public function setPublishedArticleCount($publishedArticleCount)
    {
        $this->publishedArticleCount = $publishedArticleCount;
    }

    /**
     * @return mixed
     */
    public function getPublicUrl()
    {
        return $this->publicUrl;
    }

    /**
     * @param mixed $publicUrl
     */
    public function setPublicUrl($publicUrl)
    {
        $this->publicUrl = $publicUrl;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param mixed $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedBy
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }

    /**
     * @return mixed
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}