<?php

/**
 * MemberProfile form.
 *
 * @package    form
 * @subpackage member_profile
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 * @version    SVN: $Id: sfPropelFormTemplate.php 6174 2007-11-27 06:22:40Z fabien $
 */
class MemberProfileForm extends OpenPNEFormAutoGenerate
{
  public function __construct($profileMember = array(), $options = array(), $CSRFSecret = null)
  {
    parent::__construct(array(), $options, $CSRFSecret);

    foreach ($profileMember as $profile) {
      $this->setDefault($profile->getName(), $profile->getValue());
    }
  }

  public function configure()
  {
    $this->widgetSchema->setNameFormat('profile[%s]');
  }

  public function save($memberId)
  {
    $values = $this->getValues();

    foreach ($values as $key => $value)
    {
      $profile = ProfilePeer::retrieveByName($key);
      if (!$profile)
      {
        continue;
      }

      $formType = $profile->getFormType();

      $memberProfile = MemberProfilePeer::retrieveByMemberIdAndProfileId($memberId, $profile->getId());

      if ($formType == 'checkbox')
      {
        if ($memberProfile)
        {
          $c = new Criteria();
          $c->add(MemberProfilePeer::TREE_KEY, $memberProfile->getTreeKey());
          MemberProfilePeer::doDelete($c);
        }
        if (!is_array($value))
        {
          continue;
        } 

        $memberProfile = new MemberProfile();
        $memberProfile->makeRoot();
        $memberProfile->setMemberId($memberId);
        $memberProfile->setProfileId($profile->getId());
        $memberProfile->save();
        $memberProfile->setScopeIdValue($memberProfile->getId());
        $memberProfile->save();

        foreach ($value as $v)
        {
          $mp = new MemberProfile();
          $mp->setMemberId($memberId);
          $mp->setProfileId($profile->getId());
          $mp->setProfileOptionId($v);
          $mp->insertAsLastChildOf($memberProfile);
          $mp->save();
        }
      }
      else
      {
        if (!$memberProfile)
        {
          $memberProfile = new MemberProfile();
          $memberProfile->makeRoot(); 
        }

        $memberProfile->setMemberId($memberId);
        $memberProfile->setProfileId($profile->getId());
        if ($formType == 'select' || $formType == 'radio')
        {
          $memberProfile->setProfileOptionId($value);
        }
        else
        {
          $memberProfile->setValue($value);
        }
        $memberProfile->save();
      }
    }

    return true;
  }

  public function setRegisterWidgets()
  {
    $profiles = ProfilePeer::retrieveByIsDispRegist();
    $this->setProfileWidgets($profiles);
  }

  public function setConfigWidgets()
  {
    $profiles = ProfilePeer::retrieveByIsDispConfig();
    $this->setProfileWidgets($profiles);
  }

  public function setSearchWidgets()
  {
    $profiles = ProfilePeer::retrieveByIsDispSearch();
    $this->setProfileWidgets($profiles);
  }

  protected function setProfileWidgets($profiles)
  {
    foreach ($profiles as $profile) {
      $profile_i18n = $profile->getProfileI18ns();
      $profileWithI18n = $profile->toArray() + $profile_i18n[0]->toArray();
      $this->widgetSchema[$profile->getName()] = $this->generateWidget($profileWithI18n, $this->getFormOptionsValue($profile->getId()));
      $this->validatorSchema[$profile->getName()] = $this->generateValidator($profileWithI18n, $this->getFormOptions($profile->getId()));
    }
  }

  private function getFormOptions($profileId)
  {
    $result = array();
    $options = ProfileOptionPeer::retrieveByProfileId($profileId);

    foreach ($options as $option) {
      $result[] = $option->getId();
    }

    return $result;
  }

  private function getFormOptionsValue($profileId)
  {
    $result = array();
    $options = ProfileOptionPeer::retrieveByProfileId($profileId);

    foreach ($options as $option) {
      $result[$option->getId()] = $option->getValue();
    }

    return $result;
  }

  private function updateDefaultsFromObject($obj)
  {
    $this->setDefaults(array_merge($this->getDefaults(), $obj->toArray(BasePeer::TYPE_FIELDNAME)));
  }
}
