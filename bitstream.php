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
App::import("Vendor",'Sanitize');

class Bitstream extends AppModel
{
  var $name = 'Bitstream';
  var $useTable = 'bitstream';
  var $primaryKey = 'bitstream_id';
  
  /** Return next available bitstream_id */
  function getNextId()
    {
    $this->clearDBCache();
    return self::qresult( $this->query("SELECT getnextid('bitstream')") );
    }
    
  /** get Presentations Power point */
  function getPresentations($order,$limit,$offset)
    {
    $order = Sanitize::clean($order);
    $limit = Sanitize::clean($limit);
    $offset = Sanitize::clean($offset);
    $query=$this->query("SELECT b2s.slideshare_id,b.bitstream_id, b.name,b.size_bytes , i2b.item_id , t.title
          FROM bitstream as b, bitstream2slideshare as b2s, bundle2bitstream as b2b , item2bundle as i2b,
            (SELECT item_id, text_value as title FROM metadatavalue  WHERE metadata_field_id=64 ) as t
         WHERE b2s.bitstream_id=b.bitstream_id AND b.bitstream_id=b2b.bitstream_id AND b2b.bundle_id=i2b.bundle_id
              AND t.item_id=i2b.item_id
           ORDER BY $order LIMIT $limit OFFSET $offset");
    if(empty($query))
      {
      return array();
      }
    foreach($query as $res)
      {
      $res[0]['size']=$this->getFileSizeAsString($res[0]['size_bytes']);
      $result[]=$res[0];
      }
    return $result;
    }
    
  /** Get bitstream_format_id */
  function getBitstreamFormatId($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $query = "SELECT bitstream_format_id FROM bitstream WHERE bitstream_id = '$bitstreamid'";
    $res = $this->query($query);
    if(count($res) == 0)  return false;
    return $res[0][0]['bitstream_format_id'];
    }

  /** Get Videos */
  function getVideos($order,$limit,$offset)
    {
    $order = Sanitize::clean($order);
    $limit = Sanitize::clean($limit);
    $offset = Sanitize::clean($offset);
    $query=$this->query("SELECT b2s.youtube_id,b.bitstream_id, b.name,b.size_bytes , i2b.item_id , t.title
          FROM bitstream as b, bitstream2youtube as b2s, bundle2bitstream as b2b , item2bundle as i2b,
            (SELECT item_id, text_value as title FROM metadatavalue  WHERE metadata_field_id=64 ) as t
         WHERE b2s.bitstream_id=b.bitstream_id AND b.bitstream_id=b2b.bitstream_id AND b2b.bundle_id=i2b.bundle_id
              AND t.item_id=i2b.item_id
           ORDER BY $order LIMIT $limit OFFSET $offset");
    if(empty($query))
      {
      return array();
      }
    foreach($query as $res)
      {
      $res[0]['size']=$this->getFileSizeAsString($res[0]['size_bytes']);
      $result[]=$res[0];
      }
    return $result;
    }
    
  /** Get the handle */
  function getHandle($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $itemid = $this->getItemid($bitstreamid);
    $handle = $this->query("SELECT handle FROM handle WHERE resource_id='$itemid' AND resource_type_id='2'");
    if(count($handle)>0)
      {
      return $handle[0][0]["handle"].".".$bitstreamid;
      }
    return "";
    }

  //------------Insight journal
  function getIdRevisionfiles($pubid)
  {
  $query = $this->query("SELECT file FROM isj_revisionfiles where publication='$pubid' ORDER BY revision DESC");
  if (empty($query))
    {
    return 0;
    }
  foreach($query as $id)
    {
    $result[]=$id[0]['file'];
    }
  return @$result;
  }


  function getLastRevisionFiles($pubid)
    {
    $query = $this->query("SELECT * FROM isj_revisionfiles where publication='$pubid' ORDER BY revision DESC");
    foreach($query as $res)
    {
    $result[]=$res[0]['file'];
    }
    if(!empty($result))
      {
      return $result;
      }
    return @$result;
    }

  function getRevision($bitstreamid)
    {
    $description=$this->getDescription($bitstreamid);
    $description=substr($description,11);
    $description=substr($description,0,-1);
    $result=array();
    $revision=array();

    while(!empty($description))
    {
    ereg( "[0-9]*", $description, $revision);
    $description=substr($description,strlen($revision[0])+1);
    if(!empty($revision[0]))
      {
      $result[]=$revision[0];
      }
    }
    return @$result;
    }

   function addRevisionToBistream($bitstreamid,$revision)
    {
    $description=$this->getDescription($bitstreamid);
    $description=substr($description,0,-1);
    $description.=",".$revision."]";
    $this->query("Update bitstream Set description='$description' Where bitstream_id='$bitstreamid' ");
    return true;
    }
    
  //------------- Midas
  /**  get itemid from bitstream*/
   function getItemid($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("SELECT I.item_id FROM item2bundle AS I , bundle2bitstream AS B WHERE I.bundle_id=B.bundle_id AND B.bitstream_id='$bitstreamid'");
    return $bitstream[0][0]["item_id"];
    }

  /** Check if the bitstream exists */
  function getExists($id)
    {
    if(!is_numeric($id)) return false;
    return (bool)count($this->query("SELECT bitstream_id FROM bitstream WHERE bitstream_id='$id'"));
    }

    /** Check if the bitstream exists */
  function getExistsByPath($path)
    {
    $path = Sanitize::clean($path);
    return (bool)count($this->query("SELECT bitstream_id FROM bitstream WHERE internal_id='$path'"));
    }

  /** Get all the bitstreams */
  function getAll()
    {
    $bitstreamids = array();
    $query = $this->query("SELECT bitstream_id FROM bitstream ORDER BY bitstream_id ASC ");
    foreach($query as $bitstream)
      {
      $bitstreamids[] = $bitstream[0]['bitstream_id'];
      }
    return $bitstreamids;
    }

  // Get the name of the bitstream
  function getName($bitstreamid,$type=0)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("SELECT name FROM bitstream WHERE bitstream_id='$bitstreamid'");
    return $bitstream[0][0]["name"];
    }

  // Update the name
  function updateName($bitstreamid,$name)
    {
    if(!is_numeric($bitstreamid)) return false;
    if(!$this->getExists($bitstreamid))
      {
      return;
      }
    $name = Sanitize::clean($name);
    $this->query("UPDATE bitstream SET name='$name' WHERE bitstream_id='$bitstreamid'");
    return true;
    }

  // Get the type (pdf or source code or data) (insight journal)
  function getType($bitstreamid,$type=0)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("SELECT type FROM bitstream WHERE bitstream_id='$bitstreamid'");
    return $bitstream[0][0]["type"];
    }

    //update type (pdf or source code or data)
 function updateType($bitstreamid,$type)
    {
    if(!is_numeric($bitstreamid)) return false;
    $type = Sanitize::clean($type);
   $this->query("UPDATE bitstream SET type='$type' WHERE bitstream_id='$bitstreamid'");
    return true;
    }


  // Get the description
  function getDescription($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("SELECT description FROM bitstream WHERE bitstream_id='$bitstreamid'");
    if(!$bitstream)
      {
      return "";
       }
    return $bitstream[0][0]["description"];
    }

  // Update the description
  function updateDescription($bitstreamid,$value)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("UPDATE bitstream SET description='$value' WHERE bitstream_id='$bitstreamid'");

    return true;
    }

  // Get the size
  function getSizeInBytes($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("SELECT size_bytes FROM bitstream WHERE bitstream_id='$bitstreamid'");
    return $bitstream[0][0]["size_bytes"];
    }

   // Set the size of the bitstream
   function setSizeInBytes($bitstreamid,$size)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("UPDATE bitstream SET size_bytes='$size' WHERE bitstream_id='$bitstreamid'");
    }

  // Get the date of the upload
  function getDate($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $midasbitstream = $this->query("SELECT date_uploaded FROM midas_bitstream WHERE bitstream_id ='$bitstreamid'");
    if(count($midasbitstream)>0)
      {
      return $midasbitstream[0][0]['date_uploaded'];
      }
    return "";
    }


  // Get the MD5 of the bitstream
  function getMD5($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("SELECT checksum FROM bitstream WHERE bitstream_id='$bitstreamid'");
    return $bitstream[0][0]["checksum"];
    }

   // Set the MD5
   function setMD5($bitstreamid,$md5)
    {
    if(!is_numeric($bitstreamid)) return false;
    $md5 = Sanitize::clean($md5);
    $bitstream = $this->query("UPDATE bitstream SET checksum='$md5' WHERE bitstream_id='$bitstreamid'");
    }

  // Get the source of the bitstream
  function getSource($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("SELECT source FROM bitstream WHERE bitstream_id='$bitstreamid'");
    return $bitstream[0][0]["source"];
    }

  // Update the source
  function updateSource($bitstreamid,$source)
    {
    if(!is_numeric($bitstreamid)) return false;
    $source = Sanitize::clean($source);
    if(!$this->getExists($bitstreamid))
      {
      return;
      }
    $this->query("UPDATE bitstream SET source='$source' WHERE bitstream_id='$bitstreamid'");
    return true;
    }

  // Get the user format description
  function getUserFormatDescription($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("SELECT user_format_description FROM bitstream WHERE bitstream_id='$bitstreamid'");
    return $bitstream[0][0]["user_format_description"];
    }

  // Get the mimetype
  function getMimeType($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bitstream = $this->query("SELECT bitstream_format_id FROM bitstream WHERE bitstream_id='$bitstreamid'");
    $bitstream_format_id = $bitstream[0][0]["bitstream_format_id"];
    $bitstream = $this->query("SELECT mimetype FROM bitstreamformatregistry WHERE bitstream_format_id='$bitstream_format_id'");
    return $bitstream[0][0]["mimetype"];
    }

  // get the item given the bitstream
  function getItem($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $bundles = $this->query("SELECT bundle_id FROM bundle2bitstream WHERE bitstream_id='$bitstreamid'");
    foreach($bundles as $bundle)
      {
      $bundleid = $bundle[0]["bundle_id"];
      $item =  $this->query("SELECT item_id FROM item2bundle WHERE bundle_id='$bundleid'");
      return $item[0][0]["item_id"];
      }
    return false;
    }

  /** Get the full file name of the bitstream
   *  Note: the assetstore should be prefixed */
   function getFullFilename($id)
     {
     if(!is_numeric($id))
       {
       return false;
       }

     $bitstream =  $this->query("SELECT internal_id FROM bitstream WHERE bitstream_id='$id'");
     if(empty($bitstream))
      {
      return false;
      }
     $internalid = $bitstream[0][0]["internal_id"];     
     $filename = substr($internalid,0,2)."/".substr($internalid,2,2)."/".substr($internalid,4,2)."/".$internalid;
     return $filename;
     }

  /** Function use to create the internal id */
  function microtime_float()
    {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec)*10000;
    }

  /** Create a new internal id */
  function getNewInternalId()
    {
    srand($this->microtime_float());
    $internalid = "";
    for($i=0;$i<40;$i++)
      {
      $internalid .= rand(0,9);
      }
    return $internalid;
    }

  /** Upload a bitstream without delete */
  function uploadWithoutDelete($assetstore,$name,$tempfilename,$sequenceid=1,$type=0,$description='')
    {
    return $this->upload($assetstore,$name,$tempfilename,$sequenceid,$type,$description='',true);
    } // end uploadWithoutDelete()


  /** Upload a bitstream  */
  function upload($assetstore,$name,$tempfilename,$sequenceid=1,$type=0,$description='',$nodelete=false)
    {
    include_once(VENDORS.'mimetype.php');
    $internalid = $this->getNewInternalId();

    $path = substr($internalid,0,2). DS .substr($internalid,2,2). DS .substr($internalid,4,2). DS .$internalid;
    $fullpath = $assetstore.DS.$path;

    while(file_exists($fullpath))
      {
      $internalid = $this->GetNewInternalId();
      $path = substr($internalid,0,2). DS .substr($internalid,2,2). DS .substr($internalid,4,2). DS .$internalid;
      $fullpath = $assetstore. DS .$path;
      }

    //Create the directories
    $currentdir = $assetstore.DS.substr($internalid,0,2);

    if(!file_exists($currentdir))
      {
      if(!mkdir($currentdir))
        {
        echo "Cannot make dir: ".$currentdir."\n";
        $this->log("Cannot make dir: ".$currentdir);
        return false;
        }
      chmod($currentdir,0777);
      }
    $currentdir .= DS.substr($internalid,2,2);
    if(!file_exists($currentdir))
      {
      if(!mkdir($currentdir))
        {
        $this->log("Cannot make dir2: ".$currentdir);
        return false;
        }
      chmod($currentdir,0777);
      }
    $currentdir .= DS.substr($internalid,4,2);
    if(!file_exists($currentdir))
      {
      if(!mkdir($currentdir))
        {
        $this->log("Cannot make dir3: ".$currentdir);
        return false;
        }
      chmod($currentdir,0777);
      }

    $bitstreamnextid = $this->query("SELECT getnextid('bitstream')");
    $bitstreamid = $bitstreamnextid[0][0]["getnextid"];

    // Find the mimetype
    $mime = new mimetype();
    $mimetype = $mime->getType($name);

    $bitstreamformatregistry = $this->query("SELECT bitstream_format_id FROM bitstreamformatregistry WHERE mimetype='$mimetype'");
    if(count($bitstreamformatregistry)>0)
      {
      $bitstreamformatid  = $bitstreamformatregistry[0][0]["bitstream_format_id"];
      }
    else
      {
      $bitstreamformatid = 1; //Unknown
      }
    $size = filesize($tempfilename);
    $checksum = hash_file("md5",$tempfilename);

    $bitstream = array();
    $bitstream['bitstream_id'] = $bitstreamid;
    $bitstream['bitstream_format_id'] = $bitstreamformatid;
    $bitstream['name'] = $name;
    $bitstream['description'] = $description;
    $bitstream['size_bytes'] = $size;
    $bitstream['checksum'] = $checksum;
    $bitstream['checksum_algorithm'] = 'MD5';
    $bitstream['user_format_description'] = '';
    $bitstream['source'] = $tempfilename;
    $bitstream['internal_id'] = $internalid;
    $bitstream['deleted'] = 'FALSE';
    $bitstream['store_number'] = '0';
    $bitstream['sequence_id'] = $sequenceid;
    if($type!=0)
      {
      $bitstream['type'] = $type;
      }

    if(!$this->save($bitstream))
      {
      echo "Cannot save bistream";
      $this->log("Cannot save bistream");
      return false;
      }

    if($nodelete)
      {
      // Move the file
      if(!copy($tempfilename,$fullpath))
        {
        // Should delete the bitstream
        echo "Cannot move bistream".$tempfilename." to assetstore: ".$fullpath;
        $this->log("Cannot move bistream".$tempfilename." to assetstore: ".$fullpath);
        return false;
        }
      }
    else
      {
      // Move the file
      if(!rename($tempfilename,$fullpath))
        {
        // Should delete the bitstream
        echo "Cannot move bistream".$tempfilename." to assetstore: ".$fullpath;
        $this->log("Cannot move bistream".$tempfilename." to assetstore: ".$fullpath);
        return false;
        }
      }

    // Update the sequence
    $this->query("SELECT setval('bitstream_seq', max(bitstream_id)) FROM bitstream");

    // Add the upload date to the table
    $now = date("Y-m-d H:i:s");
    $tables=$this->query("SELECT * FROM pg_tables WHERE tablename='midas_bitstream'");
    if(!empty($tables))
      {
      $midasbitstream = $this->query("INSERT into midas_bitstream (bitstream_id,date_uploaded) VALUES('$bitstreamid','$now')");
      }

    return $this->getLastInsertID();
    } // end upload()


  /** Delete a bitstream */
  function delete($bitstreamid, $assetstore='')
    {
    // check parameters  
    if(!is_numeric($bitstreamid)) return false;
    if($assetstore == '')
      {
      $assetstore = Configure::read('midas.assetstore_directory');      
      }
        
    // delete file
    $filename = $assetstore . DS . $this->getFullFilename($bitstreamid);
    //$name=@$this->getFullFilename($bitstreamid);
    if(!empty($name) && file_exists($filename))
      {
      unlink($filename);
      }
      
    // Remove the policies
    $this->query("DELETE FROM resourcepolicy WHERE resource_id='$bitstreamid' AND resource_type_id='1'");
    
    // Remove bitstreams from the bundle
    $this->query("DELETE FROM bundle2bitstream WHERE bitstream_id='$bitstreamid'");
    
    // Delete the thumbnail
    $thumbnailid = $this->getThumbnailId($bitstreamid);
    if($thumbnailid !== false)
      {
      // Delete from mderesource2thumbnail
      $this->query("DELETE FROM mderesource2thumbnail WHERE thumbnail_id='$thumbnailid'");
      // Delete from bitstream2thumbnail
      $this->query("DELETE FROM bitstream2thumbnail WHERE bitstream_id='$bitstreamid' OR thumbnail_id='$thumbnailid'");
      // Delete thumbnail associated with the bitstream
      $this->query("DELETE FROM thumbnail WHERE thumbnail_id='$thumbnailid'");
      }
      
    // Delete resource if this bitstream is the last one
    if(($resourceid = $this->getResource($bitstreamid)) !== false)
      {
      // Delete the link bitstream2resource
      $this->query("DELETE FROM bitstream2resource WHERE bitstreamid='$bitstreamid'");
      App::import('Model', 'MdeResource');
      $MdeResource = new MdeResource();
      if(($count = $MdeResource->getNumberOfBitstream($resourceid)) <= 1)
        {
        $MdeResource->delete($resourceid);
        }
      }
      
    // Delete the bitstream from the bitstream table  
    $this->query("DELETE FROM bitstream WHERE bitstream_id='$bitstreamid'");
     // Delete from midas_bistream
    $this->query("DELETE FROM midas_bitstream WHERE bitstream_id='$bitstreamid'");   
    $this->query("SELECT setval('bitstream_seq', max(bitstream_id)) FROM bitstream");

    return true;
    } // end delete

  /** Get the resourceid of the bitstream if any, otherwise return false */
  function getResource($bitstreamid)
    {
    // check parameters
    if(!is_numeric($bitstreamid)) return false;
    
    // query
    $resourceid = $this->query("SELECT resourceid FROM bitstream2resource WHERE bitstreamid='$bitstreamid' LIMIT 1");
    
    // return result
    if(empty($resourceid)) return false;
    return $resourceid[0][0]['resourceid'];    
    }
    
  /** Add a thumbnail to a bitstream */
  function addThumbnail($bitstreamid,$filename)
    {
    if(!is_numeric($bitstreamid))
      {
      return false;
      }

    // Check that the file exists
    if(!file_exists($filename))
      {
      return false;
      }
    $contents = pg_escape_bytea(file_get_contents($filename));
    $thumbnailid = $this->query("INSERT INTO thumbnail (image) VALUES ('$contents') RETURNING thumbnail_id");
    $thumbnailid = $thumbnailid[0][0]['thumbnail_id'];
    if($this->getAffectedRows()==0)
      {
      return false;
      }
    $this->query("INSERT INTO bitstream2thumbnail (bitstream_id,thumbnail_id) VALUES ('$bitstreamid','$thumbnailid')");
    if($this->getAffectedRows()==0)
      {
      return false;
      }
    return true;
    } // end addThumbnail

  /** Get a thumbnail from a bitstream */
  function getThumbnail($bitstreamid)
    {
    if(!is_numeric($bitstreamid))
      {
      return false;
      }

    $image = $this->query("SELECT thumbnail.image AS image " .
                          "FROM thumbnail, bitstream2thumbnail " .
                          "WHERE bitstream2thumbnail.bitstream_id='$bitstreamid'" .
                              "AND thumbnail.thumbnail_id=bitstream2thumbnail.thumbnail_id");
    if(count($image)>0)
      {
      return pg_unescape_bytea($image[0][0]['image']);
      }
    return "";
    } // end getThumbnail

  /** Get a thumbnail from a bitstream, return false if no thumbnail */
  function getThumbnailId($bitstreamid)
    {
    if(!is_numeric($bitstreamid))
      {
      return false;
      }

    $id = $this->query("SELECT thumbnail.thumbnail_id AS thumbnail_id " .
                          "FROM thumbnail, bitstream2thumbnail " .
                          "WHERE bitstream2thumbnail.bitstream_id='$bitstreamid'" .
                              "AND thumbnail.thumbnail_id=bitstream2thumbnail.thumbnail_id");
    if(empty($id))
      {
      return false;
      }
    return $id[0][0]['thumbnail_id'];
    }

  /** hasThumbnail */
  function hasThumbnail($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $image = $this->query("SELECT COUNT(*) FROM bitstream2thumbnail, thumbnail " .
                      "WHERE bitstream2thumbnail.bitstream_id='$bitstreamid' " .
                      "AND bitstream2thumbnail.thumbnail_id = thumbnail.thumbnail_id " .
                      "LIMIT 1");
    if($image[0][0]['count']>0)
      {
      return true;
      }
    return false;
    }

  function getXcedeResource($bitstreamid,$resourcename)
    {
    return false;
    }

  /** Get slideshare id */
  function getSlideShareId($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $slideshareid = 0;
    $query = $this->query("SELECT slideshare_id  FROM bitstream2slideshare WHERE bitstream_id='$bitstreamid'");
    if(isset($query[0]))
      {
      $slideshareid = $query[0][0]['slideshare_id'];
      }
    return $slideshareid;
    }

  /** Get youtube id */
  function getYouTubeId($bitstreamid)
    {
    if(!is_numeric($bitstreamid)) return false;
    $youtubeid = 0;
    $query = $this->query("SELECT youtube_id  FROM bitstream2youtube WHERE bitstream_id='$bitstreamid'");
    if(isset($query[0]))
      {
      $youtubeid = $query[0][0]['youtube_id'];
      }
    return $youtubeid;
    }
    

}
?>
