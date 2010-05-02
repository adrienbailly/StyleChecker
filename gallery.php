<?php
/*=========================================================================
Program:   MIDAS Server
Language:  PHP/HTML/Java/Javascript/SQL
Date:      $Date: 2009-11-19 14:02:48 -0500 (jeu. 19 nov. 2009) $
Version:   $Revision: 4697 $

Copyright (c) Kitware Inc. 28 Corporate Drive. All rights reserved.
Clifton Park, NY, 12065, USA.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php
include_once('constants.php');

App::import("Vendor",'Sanitize');
App::import("Model", "Community");

class Gallery extends AppModel
{
  // Its always good practice to include this variable.
  var $name = 'Gallery';
  var $useTable = 'eperson';

  /** Test if the community exist */
  function getCommunityExists($id)
    {
    if(!is_numeric($id)){return false;}
    return (bool)count($this->query("SELECT community_id FROM community WHERE community_id='$id'"));    
    }
 
  /** List all the subcommunities of a given community */
  function getChildCommunitys($id,$result=array())
    {
    if(!is_numeric($id)){return false;}
    if(!is_array($result)){return false;}
    $community2community_result = $this->query("SELECT child_comm_id FROM community2community WHERE parent_comm_id='$id'");  
    
      if(empty($community2community_result))
        {
        return $result;
        }
      foreach($community2community_result as $res)
        {
        $result[]=$res[0]['child_comm_id'];
        $result=$this->getChildCommunitys($res[0]['child_comm_id'],$result);
        }
     return $result;       
    }

  /** 
   * Return the keywords and the counts
   * @param $limit :
   * @param $userid : 
   */
  function getKeywords($limit=100,$userid=0)
    {  
    if(!is_numeric($limit) || !is_numeric($userid))
      {
      return false;
      }
    
    $group=$this->query("SELECT eperson_group_id
                         FROM epersongroup2eperson
                         WHERE eperson_id='$userid'");
    $condition=" resourcepolicy.epersongroup_id='0' OR resourcepolicy.eperson_id='$userid' ";
    if(!empty($group))
      {
      foreach($group as $res)
        {
        $condition .= " OR resourcepolicy.epersongroup_id='" . $res[0]['eperson_group_id'] . "' ";
        }
      }
     
    $keywords = array();
    $keyword_result = $this->query("SELECT text_value,count(*) FROM metadatavalue,resourcepolicy
                                    WHERE metadatavalue.metadata_field_id='57' 
                                    AND resourcepolicy.resource_id=metadatavalue.item_id
                                    AND resourcepolicy.resource_type_id=" . MIDAS_RESOURCE_ITEM."
                                    AND action_id=".MIDAS_POLICY_READ."
                                    AND ($condition) GROUP BY text_value LIMIT $limit");
    
    foreach($keyword_result as $keyword)
      {
      $key['value'] = $keyword[0]["text_value"];
      $key['count'] = $keyword[0]["count"];
      $keywords[] = $key;
      }
    return $keywords;
    }

 
  /** Get the images associated with an item */
  function getItemImage($itemid)
    {
    if(!is_numeric($itemid))
      {
      return false;
      }
      
    App::import('Model','Item');
    $Item = new Item();
    App::import('Model','Bitstream');
    $Bitstream = new Bitstream();
    $bitstreamids=$Item->getBitstreams($itemid);
    if(!empty($bitstreamids))
      {
      $selected=null;
      foreach($bitstreamids as $bistream)
        {
        $bistreams = $this->query("SELECT bitstream_id " .
            "FROM thumbnail AS t, bitstream2thumbnail AS bt " .
            "WHERE bt.bitstream_id='$bistream' " .
            "AND bt.thumbnail_id=t.thumbnail_id " .
            "AND t.image!=''");
        if(!empty($bistreams))
          {
          $selected=$bistreams[0][0]['bitstream_id'];
          if(strlen($Bitstream->getDescription($bistreams[0][0]['bitstream_id']))>5)
            {
            return $bistreams[0][0]['bitstream_id'];
            }
          }
        }
       return $selected;
      }
    return null;
    }

  /** returns all the pictures in the DB */
  function GetAllImagesId()
    {
    $ids = $this->query("SELECT mde_image.mde_image_id AS imageid " .
            "FROM mde_image, mde_color " .
        "WHERE mde_image.mde_image_id = mde_color.mde_image_id " .
        "AND mde_color.colorid = 1");
        
    $ret=array();
    foreach($ids as $id)
      {
      $ret[]=$id[0]['imageid'];
      }
    return $ret;
    }
  
  /** Returns the distance between 2 image*/
  function GetHistogramDistance($imageA, $imageB)
    {
    $dist = $this->query("SELECT HistoDist($imageA, $imageB);");
    return $dist[0][0]['histodist'];
    }
    
  /** Return a list of similar images based on histogram */
  function GetSimilarImages($BitstreamRefId, $numResult=1)
    {
    // Get the images id corresponding to the bitstream
    $mdeImageId = $this->GetImageIdFromBitstreamId($BitstreamRefId);
    if($mdeImageId === false) { return false; }
    
    // Get the list of all the images
    $imagesId = $this->GetAllImagesId();

    // Find the distance between imageRef and all the others images
    $dist = array();
    foreach($imagesId as $id)
      {
      //$dist[] = array($id, $this->GetHistogramDistance($imageRefId, $id));
      $dist[$id] = $this->GetHistogramDistance($mdeImageId, $id);
      }

    // sort and keep the 'numResult' closest images
    asort($dist);
    $dist = array_slice($dist, 0, $numResult, true);
    
    //debug($dist, true, true);
    return $dist;
    }
    
  /**  */
  function GetImageIdFromBitstreamId($bitstreamId)
    {
    if(!is_numeric($bitstreamId))
      {
      return false;
      }
    $imgid = $this->query("SELECT mde_image.mde_image_id AS imageid " .
        "FROM thumbnail, bitstream2thumbnail, bitstream2resource, mde_image " .
        "WHERE thumbnail.thumbnail_id = bitstream2thumbnail.thumbnail_id" .
        "  AND bitstream2thumbnail.bitstream_id = bitstream2resource.bitstreamid" .
        "  AND bitstream2resource.resourceid=mde_image.mde_resource_id" .
        "  AND bitstream2resource.bitstreamid=$bitstreamId");
    if(count($imgid) != 1) { return false; }
    return $imgid[0][0]['imageid'];
    }

  /** */
  function GetBitstreamInfoFromImageId($ImageId)
    {
    if(!is_numeric($ImageId))
      {
      return false;
      }
    $bitstreamInfo = $this->query(
      "SELECT bitstream.name AS name, bitstream.bitstream_id AS bitstream_id " .
        "FROM bitstream2resource, mde_image, bitstream " .
        "WHERE bitstream2resource.resourceid = mde_image.mde_resource_id " .
        "  AND mde_image.mde_image_id = $ImageId " .
        "  AND bitstream2resource.bitstreamid = bitstream.bitstream_id ");  
    return $bitstreamInfo[0][0];
    }   
    

    function getImages($community, 
                       $collection, 
                       $colorid, 
                       $userid, 
                       $number, 
                       $offset, 
                       $random, 
                       $count)
      {
      // policies
      $group=$this->query("SELECT eperson_group_id FROM epersongroup2eperson WHERE eperson_id='$userid'");
      $condition=" resourcepolicy.epersongroup_id='0' OR resourcepolicy.eperson_id='$userid' ";
      if(!empty($group))
        {
        foreach($group as $res)
          {
          $condition.=" OR resourcepolicy.epersongroup_id='".$res[0]['eperson_group_id']."' ";
          }
        }       
      $subQueryItemId = "SELECT resource_id FROM resourcepolicy WHERE " .
          "resourcepolicy.resource_id = ib.item_id " .
            " AND resourcepolicy.resource_type_id = ".MIDAS_RESOURCE_ITEM .
            " AND resourcepolicy.action_id = ".MIDAS_POLICY_READ .
            " AND ($condition)";

    if(!is_bool($random) || !is_numeric($userid))
      {
      return false;
      }
    if(!is_numeric($number) || !is_numeric($offset))
      {
      return false;
      }

    $color_condition = '';
    if($random===true)
      {
      $orderby='ORDER BY RANDOM()';
      }
    elseif($colorid !== false)
      {
      $color_condition = 'AND (mde_color.colorid = '.$colorid;
      $color_condition.= ' AND mde_color.weight >= 5 ' ;
      $color_condition.= ' AND mde_color.mde_image_id = mde_image.mde_image_id ' ;
      $color_condition.= ' AND mde_image.mde_resource_id = rt.mde_resource_id) ';
      $orderby = ($count===false) ? 'ORDER BY mde_color.weight DESC' : '';
      }   
    else
      {
      $orderby='';
      }
    

    $community_condition = '';
    if($this->getCommunityExists($community))
      {

      $community_list .= "-1"; // to fill the last comma.
      $community_condition= ' AND communities2item.community_id IN ('.$community_list.')';
      $community_condition.=' AND communities2item.item_id=ib.bundle_id';  
      $community_table='communities2item';
      }
    $collection_condition = '';
    if(is_numeric($collection))
      {
      $collection_table='collection2item';
      $collection_condition= ' AND collection2item.collection_id='.$collection;
      $collection_condition.=' AND collection2item.item_id=ib.item_id';
      }
      
      $query = "SELECT ";
      if($count === true) 
        {
        $query .= "COUNT(*) "; 
        }
      else
        {
        /*$query .= "t.thumbnail_id,ib.item_id,bt.bitstream_id,rt.mde_resource_id," .
            " 'null' as text_value, br.bitstreamid AS brbid , br.resourceid AS brrid ";   */
        $query .= " * " ;
        }
     $query .=  "FROM bundle2bitstream as bb, item2bundle as ib, thumbnail as t " . 
        "LEFT JOIN bitstream2thumbnail as bt USING (thumbnail_id) " .
        "LEFT JOIN mderesource2thumbnail as rt USING (thumbnail_id) " .
        "LEFT JOIN bitstream2resource as br ON rt.mde_resource_id=br.resourceid " . 
        ($colorid!==false ? "LEFT JOIN mde_image ON mde_image.mde_resource_id=br.resourceid " .
                        "LEFT JOIN mde_color ON mde_color.mde_image_id=mde_color.mde_image_id ":"") .  
        (isset($collection_table) ? ", $collection_table ":"") .
        (isset($community_table) ? ", $community_table ":"") .
        "WHERE " .
        "ib.bundle_id=bb.bundle_id " .
        "AND (" .
        ($colorid===false ? "(t.thumbnail_id=bt.thumbnail_id AND bb.bitstream_id=bt.bitstream_id AND br.resourceid IS NULL AND rt.mde_resource_id IS NULL) OR":"") .
        "(t.thumbnail_id=rt.thumbnail_id AND br.resourceid=rt.mde_resource_id " .
        "    AND br.bitstreamid=bb.bitstream_id AND br.bitstreamid=bt.bitstream_id" .
        "    $color_condition))" .
        "AND ib.item_id IN ($subQueryItemId) " .
        "$community_condition " .
        "$collection_condition ";
    if($count === false)
      {
      //$query .= $orderby." LIMIT ".$number." OFFSET ".$offset;
      $query .= " ORDER BY t.thumbnail_id ASC LIMIT ".$number." OFFSET ".$offset;
      }   
    
      $result = $this->query($query);
    if($count === true)
      {
      return $result[0][0]['count'];
      }      
      $images=array();  
      foreach($result as $image)
        {
        $images[] = $image[0];
        }    
      return $images;
      }
      
} // end class Gallery
?> 
"string"
"une autre \" string"
"multiline
   string"
