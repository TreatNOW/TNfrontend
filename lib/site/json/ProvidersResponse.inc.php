<?php

/**
 * Class ProvidersResponse
 * This is currently running a direct search against the database
 * However it should be a proxy api call against zidmi app. To be refactored at some point...
 *
 * Worth noting that in order to help with performance this script avoids creating objects
 *
 */
class ProvidersResponse extends JsonResponse {

    //config
    //TODO: move this somewhere
    const SEARCH_RADIUS = 1.609344;
    const PROVIDER_LIMIT = 10;

    //constants
    const LOCMODE_RADIAL = 'r';
    const LOCMODE_BOUNDS = 'b';

    //input params
    private $lat1;
    private $lng1;
    private $lat2;
    private $lng2;
    private $locMode;
    private $categoryId;
    private $open;
    private $requestable;
    private $langCode;

    //internals
    private $db;

	function __Construct() {
		parent::__Construct(get_class());
        $this->db = Database::GetInstance(Database::ZIDMI);
        //get input params
        //TODO: could do with some more validation
        $latLong  = Params::Get('ll');
        $latLong1 = Params::Get('ll1');
        $latLong2 = Params::Get('ll2');
        if ($latLong) {
            $this->lat1  = explode(',',$latLong)[0];
            $this->lng1 = explode(',',$latLong)[1];
            $this->locMode = self::LOCMODE_RADIAL;
        }
        else if ($latLong1 && $latLong2) {
            $this->lat1  = explode(',',$latLong1)[0];
            $this->lng1 = explode(',',$latLong1)[1];
            $this->lat2  = explode(',',$latLong2)[0];
            $this->lng2 = explode(',',$latLong2)[1];
            $this->locMode = self::LOCMODE_BOUNDS;
        }
        $this->categoryId   = Params::GetLong('categoryId');
        $this->langCode     = Params::Get('lang');
        //get providers/counters
        $providers        = array();
        $requestableCount = 0;
        $totalCount       = 0;
        foreach ($this->getProvidersRs() as $row) {
            //update counters
            $requestable = false;
            if (!is_null($row['online_provider_id'])) {
                $requestableCount++;
                $requestable = true;
            }
            $totalCount++;
            //compile address
            $address = '';
            if (!is_null($row['address_1'])) { $address .= ($address == '' ? '' : ', ').$row['address_1']; }
            if (!is_null($row['address_2'])) { $address .= ($address == '' ? '' : ', ').$row['address_2']; }
            if (!is_null($row['address_3'])) { $address .= ($address == '' ? '' : ', ').$row['address_3']; }
            if (!is_null($row['address_postref'])) { $address .= ($address == '' ? '' : ', ').$row['address_postref']; }
            //add to main array
            $providers[] = array('id'          => $row['id'],
                                 'reference'   => $row['partner_reference'],
                                 'name'        => $row['name'],
                                 'address1'    => $row['address_1'],
                                 'address'     => $address,
                                 'latitude'    => $row['latitude'],
                                 'longitude'   => $row['longitude'],
                                 'distance'    => $row['distance'],
                                 'requestable' => $requestable,
                                 'categories'  => $this->getProviderCategories($row['id'])
            );
        }
        //if no providers then get closest
        $nearestProvider = array();
        if ($totalCount == 0) {
            $distance = null;
            $provider = $this->getNearestProvider($this->lat1, $this->lng1, $distance);
            if (!is_null($provider)) {
                //set provider image
                $imageUri = '/images/generic-venue.png';
                if (!is_null($provider->getPrimaryImageId())) {
                    $image = new Image($provider->getPrimaryImageId());
                    $imageUri = Application::GetCdnUri($image, 100, 100);
                }
                $nearestProvider = array('id'          => $provider->getId(),
                                         'reference'   => $provider->getPartnerReference(),
                                         'name'        => $provider->getName(),
                                         'address'     => $provider->getAddress(),
                                         'latitude'    => $provider->getLatitude(),
                                         'longitude'   => $provider->getLongitude(),
                                         'imageUri'    => $imageUri,
                                         'distance'    => $distance,
                                         'categories'  => $this->getProviderCategories($provider->getId()));
            }
        }
        //done
        $this->jsonData = array('requestableCount' => $requestableCount,
                                'totalCount'       => $totalCount,
                                'providers'        => $providers,
                                'nearestProvider'  => $nearestProvider);
    }

    //TODO: this is going to return duplicates once heartbeats are working...
    function getProvidersRs() {
        if ($this->locMode == self::LOCMODE_RADIAL) {
            $distanceCall = 'distance_km(p.latitude,p.longitude,'.Database::SqlNumeric($this->lat1).",".Database::SqlNumeric($this->lng1).')';
        }
        $sql = "SELECT          p.id,
                                p.partner_reference,
                                p.name,
                                p.address_1,
                                p.address_2,
                                p.address_3,
                                p.address_postref,
                                p.latitude,
                                p.longitude,
                                op.id AS online_provider_id ";
        if ($this->locMode == self::LOCMODE_RADIAL) {
            $sql .= ",".$distanceCall." AS distance ";
        }
        else {
            $sql .= ",null AS distance ";
        }
        $sql .= "FROM            provider AS p
                 LEFT OUTER JOIN online_provider AS op ON p.id = op.provider_id
                 WHERE           p.active = 'T'
                 AND             p.partner_code = ".Database::SqlString(Config::Get(Application::CONFIG_PARTNERCODE))." ";
        switch ($this->locMode) {
            case self::LOCMODE_RADIAL:
                $sql .= "AND         ".$distanceCall." < ".self::SEARCH_RADIUS." ";
                break;
            case self::LOCMODE_BOUNDS:
                $sql .= "AND    p.latitude  BETWEEN ".$this->lat1." AND ".$this->lat2." ";
                $sql .= "AND    p.longitude BETWEEN ".$this->lng1." AND ".$this->lng2." ";
                break;
        }
        if (!is_null($this->categoryId)) {
            $sql .= "AND         p.id IN (SELECT     s.provider_id
                                          FROM       service AS s
                                          INNER JOIN service_category_cache AS scc ON s.id = scc.service_id
                                          WHERE      scc.category_id = ".Database::SqlInt($this->categoryId).") ";
        }
        //HACK: making sure email/sms option enabled for requests
        $sql .= "AND (p.email_requests = 'T' OR p.sms_requests = 'T') ";
        //sorting
        if ($this->locMode == self::LOCMODE_RADIAL) {
            $sql .= "ORDER BY    ".$distanceCall." ASC ";
        }
        else {
            $sql .= "ORDER BY    p.name ASC ";
        }
        //get
        $rs = $this->db->getResultset($sql);
        return $rs;
    }

    function getProviderCategories($providerId) {
        $categories = array();
        $sql = "SELECT DISTINCT category_id
                FROM 		    service_category_cache AS scc
                INNER JOIN	    service as s ON scc.service_id = s.id
                WHERE		    s.active = 'T'
                AND  			s.provider_id = ".Database::SqlInt($providerId);
        foreach ($this->db->getResultset($sql) as $row) {
            $categories[] = Database::GetInt($row['category_id']);
        }
        return $categories;
    }

    function getNearestProvider($lat, $lng, &$distance) {
        $provider = null;
        $rs = $this->db->getResultset("SELECT 	 id, distance_km(latitude,longitude,".Database::SqlNumeric($lat).",".Database::SqlNumeric($lng).") AS distance_km
                                       FROM 	 provider
                                       WHERE     partner_code = 'TREAT'
                                       AND       active = 'T'
                                       ORDER BY  distance_km ASC
                                       LIMIT 	 1");
        if (count($rs) > 0) {
            $provider = new Provider($rs[0]['id']);
            $distance = $rs[0]['distance_km'];
        }
        return $provider;
    }

}
?>