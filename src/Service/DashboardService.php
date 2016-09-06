<?php

namespace ChurchCRM\Service;

use ChurchCRM\Base\ListOption;
use ChurchCRM\FamilyQuery;
use ChurchCRM\ListOptionQuery;
use ChurchCRM\Map\ListOptionTableMap;
use ChurchCRM\Map\PersonTableMap;
use ChurchCRM\Person;
use ChurchCRM\PersonQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;

class DashboardService
{

  function getFamilyCount()
  {
    $person  = FamilyQuery::create()->withColumn('count(*)', "count")->findOne();
    return $data = ['familyCount' => $person->getCount()];
  }

  function getPersonCount()
  {
    $family  = PersonQuery::create()->withColumn('count(*)', "count")->findOne();
    $data = ['personCount' => $family->getCount()];
    return $data;
  }


  function getPersonStats()
  {
    $dbData = ListOptionQuery::create()
      ->addJoin(ListOptionTableMap::COL_LST_OPTIONID, PersonTableMap::COL_PER_CLS_ID)
      ->addGroupByColumn(PersonTableMap::COL_PER_CLS_ID)
      ->withColumn('count(*)', "count")
      ->filterById(1)
      ->find();
    $data = array();
    foreach ($dbData as $row) {
      $data[$row->getOptionName()] = $row->getVirtualColumn("count");
    }
    return $data;
  }

  function getDemographic()
  {
    $stats = array();
    $sSQL = "select count(*) as numb, per_Gender, per_fmr_ID from person_per group by per_Gender, per_fmr_ID order by per_fmr_ID;";
    $rsGenderAndRole = RunQuery($sSQL);
    while ($row = mysql_fetch_array($rsGenderAndRole)) {
      switch ($row['per_Gender']) {
        case 0:
          $gender = "Unknown";
          break;
        case 1:
          $gender = "Male";
          break;
        case 2:
          $gender = "Female";
          break;
        default:
          $gender = "Other";
      }

      switch ($row['per_fmr_ID']) {
        case 0:
          $role = "Unknown";
          break;
        case 1:
          $role = "Head of Household";
          break;
        case 2:
          $role = "Spouse";
          break;
        case 3:
          $role = "Child";
          break;
        default:
          $role = "Other";
      }

      $stats["$role - $gender"] = $row['numb'];
    }
    return $stats;
  }

  function getGroupStats()
  {
    $sSQL = "select
        (select count(*) from group_grp) as Groups,
        (select count(*) from group_grp where grp_Type = 4 ) as SundaySchoolClasses,
        (select count(*) from person_per,group_grp grp, person2group2role_p2g2r person_grp  where person_grp.p2g2r_rle_ID = 2 and grp_Type = 4 and grp.grp_ID = person_grp.p2g2r_grp_ID  and person_grp.p2g2r_per_ID = per_ID) as SundaySchoolKidsCount
        from dual ;";
    $rsQuickStat = RunQuery($sSQL);
    $row = mysql_fetch_array($rsQuickStat);
    $data = ['groups' => $row['Groups'], 'sundaySchoolClasses' => $row['SundaySchoolClasses'], 'sundaySchoolkids' => $row['SundaySchoolKidsCount']];
    return $data;
  }
}
