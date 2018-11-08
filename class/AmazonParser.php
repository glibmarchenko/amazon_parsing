<?php

/**
 * Class AmazonParser
 *
 * This class can get values from the pages of the service Amazon
 */
class AmazonParser{

    public static $content = '';
    public static $getFields = ['title','asin', 'description', 'price', 'specifications', 'images'];
    public static $productData = [];


    /**
     * Getting product data from Amazon page by URL
     *
     * @param string $url
     * @param array $fields
     * @return string
     */
    public static function getAmazonData(string $url, array $fields = [])
    {
        self::getDataFromAmazon($url);

        $fields = empty($fields) ? self::$getFields : $fields;

        foreach ($fields as $field) {
            $methodName = self::getMethodName($field);
            self::$methodName();
        }

        return json_encode(self::$productData);
    }

    /**
     *  Getting the product title
     *
     */
    public static function getTitle()
    {
        self::$productData['title'] = trim(self::$content->find('span[id="productTitle"]', 0)->innertext);
    }

    /**
     *  Getting a unique product number
     *
     */
    public static function getAsin()
    {
        self::$productData['asin'] = trim(self::$content->find('input[id="ASIN"]', 0)->getAttribute('value'));
    }

    /**
     *  Getting the price and currency code of the product
     *
     */
    public static function getPrice()
    {
        $priceValue = trim(self::$content->find('div[id="cerberus-data-metrics"]', 0)->getAttribute('data-asin-price'));
        $priceCurrency = trim(self::$content->find('div[id="cerberus-data-metrics"]', 0)->getAttribute('data-asin-currency-code'));

        self::$productData['price'] = [
            'value'     => (float)$priceValue,
            'currency'  => $priceCurrency
        ];
    }

    /**
     *  Getting product description
     *
     */
    public static function getDescription()
    {
        $result = strip_tags(self::$content->find('div[id="productDescription"]', 0)->innertext);

        self::$productData['description'] = trim(preg_replace('/\t+/', '', $result));
    }

    /**
     *  Get technical specifications
     *
     */
    public static function getSpecifications()
    {
        $table = trim(self::$content->find('div[id="prodDetails"]', 0));

        if (!empty($table)){
            $removeFields = ['best_sellers_rank', 'delivery_destinations'];

            self::$productData['specifications'] = self::getDataFromTable($table, $removeFields);
        }
    }

    /**
     * Get images from Amazon page in original and thumbnail format
     * Saves $productData to the common array
     *
     */
    public static function getImages()
    {
        $result = [];
        $imagesContent = trim(self::$content->find('div[id="imageBlock_feature_div"]', 0)->find('script', 2)->innertext);
        $clearImagesContainer = substr($imagesContent, 66);
        $clearImagesContainer = substr($clearImagesContainer, 0, -77);
        $clearImagesContainer = str_replace('\'', '"', $clearImagesContainer);
        $images = (array)json_decode(trim($clearImagesContainer))->colorImages->initial;

        foreach ($images as $image) {
            $result[] = [
                'main' => $image->hiRes,
                'thumbnail' => $image->thumb
            ];
        }

        self::$productData['images'] = $result;

    }

    /**
     * Generation of the Headers, preparation and parsing pages from the service Amazon
     *
     * @param $url
     * @return mixed
     */
    protected static function getWebPage($url)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER  => true,     // return web page
            CURLOPT_HEADER          => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION  => true,     // follow redirects
            CURLOPT_ENCODING        => "",       // handle all encodings
            CURLOPT_USERAGENT       => "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36", // who am i
            CURLOPT_AUTOREFERER     => true,     // set referer on redirect
            CURLOPT_REFERER         => "google.com",
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_SSL_VERIFYPEER  => false
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;

        return $header;
    }

    /**
     * Saving HTML to a variable
     *
     * @param string $url
     */
    protected static function getDataFromAmazon(string $url)
    {
        $content = self::getWebPage(urldecode($url))['content'];

        self::$content = str_get_html($content);
    }

    /**
     * Generates method name by called field
     *
     * @param string $name
     * @param string $prefix
     * @return string
     */
    protected static function getMethodName(string $name, string $prefix = 'get')
    {
        return $prefix . ucfirst($name);
    }

    /**
     * Helper method for taking data from a table, takes an array with values that need to be excluded
     *
     * @param string $table_html
     * @param array $remove_fields
     * @return array
     */
    protected static function getDataFromTable(string $table_html, array $remove_fields = [])
    {
        $result = [];
        $rows = str_get_html($table_html)->find('tr');

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $label = trim($row->find('td[class="label"]',0)->innertext);
                $value = trim(strip_tags($row->find('td[class="value"]',0)->innertext));
                $key = self::stringToKey($label);

                if ($label && $value && !in_array($key, $remove_fields)) {
                    $result[$key] = [
                        'label' => $label,
                        'value' => $value,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Helper method to generate an array key
     *
     * @param string $string
     * @return string
     */
    protected static function stringToKey(string $string)
    {
        $removeSymbols = ['?', '.', ':', '(', ')'];
        $clearString = str_replace($removeSymbols, '', trim($string));
        return str_replace(' ', '_', strtolower($clearString));
    }

}