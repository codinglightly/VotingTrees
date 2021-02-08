<?php

class VTvotingTrees
{
  var $mysqli_link;

  function VTvotingTrees($mysqli_link)
  {
    $this->mysqli_link=$mysqli_link;
  }
  
  function getIssueById($id)
  {
    $query = "SELECT * FROM VTissue WHERE id=$id";
    $result = mysqli_query($this->mysqli_link,$query);
    if ( $row=mysqli_fetch_array( $result )){
      return new VTissue( $row, $this->mysqli_link);
    }
    else{
      return NULL;
    }
  }

  function getIssueByName($name)
  {
    $query = "SELECT * FROM VTissue WHERE name like '$name'";
    $result = mysqli_query($this->mysqli_link,$query);
    if ( $row=mysqli_fetch_array( $result )){
      return new VTissue( $row, $this->mysqli_link);
    }
    else{
      return NULL;
    }
  }
  
  function getUserPendings($uid)
  {
    $rows=Array();
    $query = "SELECT VTissue.*,VTdelegate.*,VTpending.to_user_id as pendingto ,VTpending.from_user_id FROM VTissue, VTdelegate, VTpending 
                 WHERE VTpending.issue_id = VTdelegate.issue_id AND VTdelegate.user_id = from_user_id AND VTissue.id = VTdelegate.issue_id AND to_user_id=$uid";
    
    $result = mysqli_query($this->mysqli_link,$query);

    // TODO: error handling
    // echo mysqli_error($this->mysqli_link);

    while ( $row = mysqli_fetch_array( $result )){
      $rows[]=$row;
    }
    return $rows;
  }

  function getUserIssues($uid)
  {
    $rows=Array();
    $query = "SELECT VTissue.*,VTdelegate.*,VTpending.to_user_id AS pendingto FROM VTissue, VTdelegate LEFT OUTER JOIN VTpending 
                 ON VTpending.issue_id = VTdelegate.issue_id AND VTdelegate.user_id = from_user_id WHERE VTissue.id = VTdelegate.issue_id AND user_id=$uid";
    
    $result = mysqli_query($this->mysqli_link,$query);

    // TODO: error handling
    // echo mysqli_error($this->mysqli_link);

    while ( $row = mysqli_fetch_array( $result )){
      $rows[]=$row;
    }
    return $rows;
  }

  function getUserIssue($uid,$iid)
  {
    $rows=Array();
    $query = "SELECT * FROM VTdelegate, VTissue WHERE VTissue.id=issue_id AND issue_id=$iid AND user_id=$uid";

    $result = mysqli_query($this->mysqli_link,$query);

    // TODO: error handling
    // echo mysqli_error($this->mysqli_link);

    
    $row = mysqli_fetch_array( $result );
    return $row;
  }

  function addUserIssueByName($uid,$name,$force=FALSE)
  {
    $issue=$this->getIssueByName($name);
    if ($issue==NULL){
      if ($force==TRUE)
	{
	  $iid=$this->addIssue($name);
	  if ($iid!=NULL)
	    {
	      $issue=$this->getIssueById($iid);
	    }
	  else
	    {
	      return -3;
	    }
	}
      else
	{
	  return -2;
	}
    }
    return $issue->takeCare($uid);
  }
    
  function getFriendsIssues($friends,&$issues,$uid,&$takingcares)
  {

    $query="CREATE TEMPORARY TABLE IF NOT EXISTS tmpfriends (friend_id BIGINT (20)) engine=memory";
    $result = mysqli_query($this->mysqli_link,$query);
    $query="DELETE FROM tmpfriends";
    $result = mysqli_query($this->mysqli_link,$query);
    $fstring=implode('),(', array_map('implode', array_fill(0, count($friends), ','), $friends));
    $query="INSERT INTO tmpfriends (friend_id) VALUES (".$fstring.")";
    $result = mysqli_query($this->mysqli_link,$query);
    $query="SELECT * FROM VTdelegate, tmpfriends WHERE friend_id=user_id";
    $result = mysqli_query($this->mysqli_link,$query);
    $rows=Array();
    $issues=Array();
    while ( $row = mysqli_fetch_assoc( $result )){
      $rows[$row['issue']]['friends'][]=$row;
      $issues[$row['issue']]=$row['issue'];
    }
    $query="CREATE TEMPORARY TABLE IF NOT EXISTS tmpissues (iid INT (10)) engine=memory";
    $result = mysqli_query($this->mysqli_link,$query);
    $query="DELETE FROM tmpissues";
    $result = mysqli_query($this->mysqli_link,$query);
    $query="INSERT INTO tmpissues (iid) VALUES (".implode("),(",$issues).")";
    $result = mysqli_query($this->mysqli_link,$query);
    $query="SELECT VTissue.*,tmpissues.*,VTdelegate.user_id FROM VTissue, tmpissues LEFT OUTER JOIN VTdelegate ON iid=issue_id AND user_id=$uid WHERE iid=VTissue.id";
    $result = mysqli_query($this->mysqli_link,$query);
    while ( $row = mysqli_fetch_assoc( $result )){
      $issues[$row['iid']]=Array();
      $issues[$row['iid']]['name']=$row['name'];
      $issues[$row['iid']]['total']=$row['total'];
      $takingcares[$row['iid']]=$row['user_id'];
    }
    return $rows;
  }

  function getMostPopularIssues($maxcount=100)
  {
    $query="SELECT count(user_id) as users,VTissue.* FROM VTdelegate,VTissue WHERE VTdelegate.issue_id=VTissue.id GROUP BY VTissue.id ORDER BY users DESC LIMIT $maxcount";
    $result = mysqli_query($this->mysqli_link,$query);
    while ( $row = mysqli_fetch_assoc( $result )){
      $rows[]=$row;
    }
    return $rows;
  }

  function getIssueSuggests($q,$maxcount=20)
  {
    $q=mysqli_real_escape_string($q);
    $qlike="\"%$q%\"";
    $query="SELECT count(user_id) as users,VTissue.id as id,VTissue.name FROM VTdelegate,VTissue ".
      "WHERE VTdelegate.issue_id=VTissue.id GROUP BY id ".
      "HAVING users>0 and VTissue.name like $qlike ORDER BY users DESC LIMIT $maxcount";
    $result = mysqli_query($this->mysqli_link,$query);
    while ( $row = mysqli_fetch_assoc( $result )){
      $rows[]=$row;
    }
    return $rows;
  }
  
    
  function addIssue($name)
  {
    $query="INSERT INTO VTissue(name) VALUES ('$name')";
    $result = mysqli_query($this->mysqli_link,$query);
    return mysqli_insert_id($this->mysqli_link);
  }
    
  function getIssueList()
  {
    // TODO:
  }
    
  function IntegrityCheck()
  {
    // TODO: implement
  }

}

class VTissue
{
  var $mysqli_link;
  var $id;
  var $name;
  var $total;
  
  function VTissue($row,$mysqli_link)
  {
    $this->mysqli_link=$mysqli_link;
    $this->id=$row['id'];
    $this->name=$row['name'];
    $this->total=$row['total'];
  }

  function getName()
  {
    return $this->name;
  }

  function getTotal()
  {
    return $this->total;
  }
  
  function getDelegate($uid)
  {
    $id=$this->id;
    
    $query = "SELECT * FROM VTdelegate WHERE issue_id=$id AND user_id=$uid";

    $result = mysqli_query($this->mysqli_link,$query);
    $row=mysqli_fetch_array($result);
    // TODO: error handling
    // echo mysqli_error($this->mysqli_link);
    
    return $row;
  }

  function getSupportersOf($uid)
  {
    $id=$this->id;
    $rows=Array();
    
    $query = "SELECT * FROM VTdelegate WHERE issue_id=$id AND delegate_to=$uid";
    
    $result = mysqli_query($this->mysqli_link,$query);
    while ( $row = mysqli_fetch_array( $result )){
      $rows[]=$row;
    }
    return $rows;
  }


  // users take care about the issue
  function takeCare($user)
  {
    $id=$this->id;
    
    // lock tables pending, VTdelegate
    $query="LOCK TABLES VTpending WRITE, VTdelegate WRITE";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);
    
    $query="CALL takeCare($id,$user,@o_steps,@o_result)";
    
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    $query = "select @o_steps,@o_result";
    $result=mysqli_query($this->mysqli_link,$query);
    $result_vals=mysqli_fetch_array($result);

    $row=mysqli_fetch_array($result);

    $query="UNLOCK TABLES";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    $query="UPDATE VTissue SET VTissue.total=(SELECT count(*) from VTdelegate where VTissue.id=VTdelegate.issue_id) where VTissue.id=$id";
    mysqli_query($this->mysqli_link,$query);

    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    return $result_vals['@o_result'];
  }

  // user is giving up on issue
  function unTakeCare($user)
  {
    // lock tables pending, VTdelegate
    $id=$this->id;
    
    // lock tables pending, VTdelegate
    $query="LOCK TABLES VTpending WRITE, VTdelegate WRITE";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);
    
    $query="CALL unTakeCare($id,$user,@o_result)";
    
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    $query = "select @o_result";
    $result=mysqli_query($this->mysqli_link,$query);
    $result_vals=mysqli_fetch_array($result);

    $row=mysqli_fetch_array($result);

    $query="UNLOCK TABLES";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    $query="UPDATE VTissue SET VTissue.total=(SELECT count(*) from VTdelegate where VTissue.id=VTdelegate.issue_id) where VTissue.id=$id";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    return $result_vals['@o_result'];
  }

  // user is delegating his issue to another user
  function delegate($fromuser,$touser)
  {
    // lock tables pending, VTdelegate
    $id=$this->id;
    
    $query="LOCK TABLES VTpending WRITE, VTdelegate WRITE";
    // print $query;
    
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    $query="CALL delegate($id,$fromuser,$touser,@o_steps,@o_result)";
    // print $query;
    
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    $query = "select @o_steps,@o_result";
    $result=mysqli_query($this->mysqli_link,$query);
    $result_vals=mysqli_fetch_array($result);

    $row=mysqli_fetch_array($result);
    //    print_r( $row );

    $query="UNLOCK TABLES";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);
    return $result_vals['@o_result'];
  }

  // user takes his issue into his hands
  function unDelegate($user)
  {
    // lock tables pending, VTdelegate
    $id=$this->id;
    
    $query="LOCK TABLES VTpending WRITE, VTdelegate WRITE";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);
    
    $query="CALL unDelegate($id,$user,@o_steps,@o_result)";
    //    print $query;
    
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    $query = "select @o_steps,@o_result";
    $result=mysqli_query($this->mysqli_link,$query);
    $result_vals=mysqli_fetch_array($result);

    $row=mysqli_fetch_array($result);
    //    print_r( $row );

    $query="UNLOCK TABLES";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    // debuging **************************
    //    $query="SELECT * FROM temp_results";
    
    //    $result=mysqli_query($this->mysqli_link,$query);
    //    print mysqli_error($this->mysqli_link);

    //    while($row=mysqli_fetch_array($result))
    //      {
    //	print("<br>");
	
    //	print_r($row);
    //      }
    // ***********************************

    return $result_vals['@o_result'];
  }

  function getTreeTops(&$treetops)
  {
    $id=$this->id;
    
    $query="LOCK TABLES VTpending READ, VTdelegate READ";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);
    
    $query="SELECT * FROM VTdelegate WHERE issue_id=$id AND delegate_to IS NULL ORDER BY strength DESC";
    
    $result=mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    while($row=mysqli_fetch_array($result))
      {
	$treetops[]=$row;
      }
    $query = "select @o_steps,@o_result";
    $result=mysqli_query($this->mysqli_link,$query);
    $result_vals=mysqli_fetch_array($result);

    $query="UNLOCK TABLES";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    return $result_vals['@o_result'];
  }

  
  function getDelegateTree()
  {
  }
  
  function getPathToTop($user,&$pathArray)
  {
    // lock tables pending, VTdelegate
    $id=$this->id;
    
    $query="LOCK TABLES VTpending READ, VTdelegate READ";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);
    
    $query="CALL treetopPath($id,$user,@o_steps,@o_result)";
    
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    $query = "select @o_steps,@o_result";
    $result=mysqli_query($this->mysqli_link,$query);
    $result_vals=mysqli_fetch_array($result);

    $query="UNLOCK TABLES";
    mysqli_query($this->mysqli_link,$query);
    // TODO: error handling
    // print mysqli_error($this->mysqli_link);

    $pathArray=Array();
    $query = "select * from temp_results";
    $result_path=mysqli_query($this->mysqli_link,$query);
    while($row=mysqli_fetch_array($result_path))
      {
	$pathArray[]=$row;
      }
    return $result_vals['@o_result'];
  }
}

class VTuser
{
  var $user;

  function VTuser($user,&$mysqli_link)
  {
    $this->$mysqli_link=&$mysqli_link;
    $this->user=$user;
  }

}


?>
