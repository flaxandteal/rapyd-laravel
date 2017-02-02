<?php

namespace Zofe\Rapyd\DataForm\Field;

use Collective\Html\FormFacade as Form;


class Osmap extends Field
{

    public $type = "osmap";
    public $lat = "lat";
    public $lon = "lon";
    public $zoom = 12;
    public $key;
    public $attributeIsLatLon = false;

    public function latlon($lat, $lon)
    {
        $this->lat = $lat;
        $this->lon = $lon;
        return $this;
    }

    public function zoom($zoom)
    {
        $this->zoom = $zoom;
        return $this;
    }

    public function key($key)
    {
        $this->key = $key;
        return $this;
    }

    public function attributeIsLatLon($attributeIsLatLon)
    {
        $this->attributeIsLatLon = $attributeIsLatLon;
        return $this;
    }

    public function getValue()
    {
        $process = (\Input::get('search') || \Input::get('save')) ? true : false;
        
        if ($this->request_refill == true && $process && \Input::exists($this->lat) ) {
            $this->value['lat'] = \Input::get($this->lat);
            $this->value['lon'] = \Input::get($this->lon);
            $this->is_refill = true;
            
        } elseif (($this->status == "create") && ($this->insert_value != null)) {
            $this->value = $this->insert_value;
        } elseif (($this->status == "modify") && ($this->update_value != null)) {
            $this->value = $this->update_value;
        } elseif (isset($this->model)) {
            if ($this->attributeIsLatLon) {
                list($this->value['lat'], $this->value['lon']) = $this->model->getAttribute($this->name);
            }
            else {
                $this->value['lat'] = $this->model->getAttribute($this->lat);
                $this->value['lon'] = $this->model->getAttribute($this->lon);
            }
            $this->description =  implode(',', array_values($this->value));
        }
    }


    public function getNewValue()
    {
        $process = (\Input::get('search') || \Input::get('save')) ? true : false;
        if ($process && \Input::exists($this->lat)) {
            $this->new_value['lat'] = \Input::get($this->lat);
            $this->new_value['lon'] = \Input::get($this->lon);

        } elseif (($this->action == "insert") && ($this->insert_value != null)) {
            $this->edited = true;
            $this->new_value = $this->insert_value;
        } elseif (($this->action == "update") && ($this->update_value != null)) {
            $this->edited = true;
            $this->new_value = $this->update_value;
        }
    }
    
    public function autoUpdate($save = false)
    {
        if (isset($this->model))
        {
            $this->getValue();
            $this->getNewValue();
            if ($this->attributeIsLatLon) {
                $this->model->setAttribute($this->name, [$this->new_value['lat'], $this->new_value['lon']]);
            }
            else {
                $this->model->setAttribute($this->lat, $this->new_value['lat']);
                $this->model->setAttribute($this->lon, $this->new_value['lon']);
            }

            if ($save) {
                return $this->model->save();
            }
        }
        return true;
    }
    
    public function build()
    {
        $output = "";
        $this->attributes["class"] = "form-control";
        if (parent::build() === false)
            return;

        switch ($this->status) {
            case "disabled":
            case "show":

                if ($this->type == 'hidden' || $this->value == "") {
                    $output = "";
                } elseif ((!isset($this->value))) {
                    $output = $this->layout['null_label'];
                } else {
                    $output  = Form::hidden($this->lat, $this->value['lat'], ['id'=>$this->lat]);
                    $output .= Form::hidden($this->lon, $this->value['lon'], ['id'=>$this->lon]);
                    $output .= '<div id="map_'.$this->name.'" class="map" style="width:100%; height:500px"></div>';
                    $output .= '<script src="https://openlayers.org/en/v3.20.1/build/ol.js" type="text/javascript"></script>';

                    \Rapyd::script("

                    function initialize()
                    {
                        var latitude = document.getElementById('{$this->lat}');
                        var longitude = document.getElementById('{$this->lon}');
                        var zoom = {$this->zoom};

                        var latLng = ol.proj.fromLonLat([parseFloat(longitude.value), parseFloat(latitude.value)]);
                        var point = new ol.geom.Point(latLng);

                        var pointFeature = new ol.Feature({
                            geometry: point,
                            name: 'Epicentre'
                        });

                        var vectorSource = new ol.source.Vector({})
                        var vectors = new ol.layer.Vector({
                            source: vectorSource,
                            style: new ol.style.Style({
                                image: new ol.style.Circle({
                                    fill: new ol.style.Fill({
                                        color: [255, 0, 0]
                                    }),
                                    radius: 10
                                })
                            })
                        })
                        vectorSource.addFeature(pointFeature);

                        var mapOptions = {
                            target: 'map_{$this->name}',
                            layers: [
                                new ol.layer.Tile({
                                    source: new ol.source.OSM()
                                }),
                                vectors
                            ],
                            view: new ol.View({
                                zoom: zoom,
                                center: latLng
                            }),
                        }

                        var map = new ol.Map(mapOptions);
                    }
                    initialize();
                ");
                }
                $output = "<div class='help-block'>" . $output . "</div>";
                break;

            case "create":
            case "modify":
                $output  = Form::hidden($this->lat, $this->value['lat'], ['id'=>$this->lat]);
                $output .= Form::hidden($this->lon, $this->value['lon'], ['id'=>$this->lon]);
                $output .= '<div id="map_'.$this->name.'" class="map" style="width:100%; height:500px"></div>';
                $output .= '<script src="https://openlayers.org/en/v3.20.1/build/ol.js" type="text/javascript"></script>';
                
            \Rapyd::script("
        
            function initialize()
            {
                var latitude = document.getElementById('{$this->lat}');
                var longitude = document.getElementById('{$this->lon}');
                var zoom = {$this->zoom};

                var latLng = ol.proj.fromLonLat([parseFloat(longitude.value), parseFloat(latitude.value)]);
                var point = new ol.geom.Point(latLng);

                var pointFeature = new ol.Feature({
                    geometry: point,
                    name: 'Epicentre'
                });

                var vectorSource = new ol.source.Vector({})
                var vectors = new ol.layer.Vector({
                    source: vectorSource,
                    style: new ol.style.Style({
                        image: new ol.style.Circle({
                            fill: new ol.style.Fill({
                                color: [255, 0, 0]
                            }),
                            radius: 10
                        })
                    })
                })
                vectorSource.addFeature(pointFeature);

                var mapOptions = {
                    target: 'map_{$this->name}',
                    layers: [
                        new ol.layer.Tile({
                            source: new ol.source.OSM()
                        }),
                        vectors
                    ],
                    view: new ol.View({
                        zoom: zoom,
                        center: latLng
                    }),
                }
        
                var map = new ol.Map(mapOptions);

                var update_hidden_fields = function (ev) {
                    var lonLat = ol.proj.toLonLat(ev.coordinate);
                    latitude.value = lonLat[0];
                    longitude.value = lonLat[1];
                }

                var translate = new ol.interaction.Translate({
                    features: new ol.Collection([pointFeature])
                });
                translate.on('translateend', update_hidden_fields);
                map.addInteraction(translate);
            }
            initialize();
        ");
                

                break;

            case "hidden":
                $output = '';//Form::hidden($this->db_name, $this->value);
                break;

            default:;
        }
        $this->output = "\n" . $output . "\n" . $this->extra_output . "\n";
    }

}
