<?php

namespace Vesta\ControlPanel;

use Cissee\WebtreesExt\ViewUtils;
use Fisharebest\Webtrees\Bootstrap4;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\GedcomTag;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleInterface;
use Symfony\Component\HttpFoundation\Request;
use TheSeer\Tokenizer\Exception;
use Vesta\ControlPanel\Model\ControlPanelCheckbox;
use Vesta\ControlPanel\Model\ControlPanelElement;
use Vesta\ControlPanel\Model\ControlPanelFactRestriction;
use Vesta\ControlPanel\Model\ControlPanelPreferences;
use Vesta\ControlPanel\Model\ControlPanelRadioButtons;
use Vesta\ControlPanel\Model\ControlPanelRange;
use Vesta\ControlPanel\Model\ControlPanelSection;
use Vesta\ControlPanel\Model\ControlPanelSubsection;

class ControlPanelUtils {

  private $module;

  /**
   * 
   * @param ModuleInterface $module
   */
  public function __construct(ModuleInterface $module) {
    $this->module = $module;
  }

  /**
   * 
   * @return void
   */
  public function printPrefs(ControlPanelPreferences $prefs, $module) {
    ?>
    <h1><?php echo I18N::translate('Preferences'); ?></h1>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="route" value="module">
        <input type="hidden" name="module" value="<?php echo $module; ?>">
        <input type="hidden" name="action" value="Admin">
        <?php
        foreach ($prefs->getSections() as $section) {
          $this->printSection($section);
        }
        ?>

        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i>
                    <?php echo I18N::translate('save'); ?>
                </button>
            </div>
        </div>
    </form>
    <?php
  }

  /**
   * 
   * @return void
   */
  public function printSection(ControlPanelSection $section) {
    ?>
    <h3><?php echo $section->getLabel(); ?></h3>
    <?php
    $description = $section->getDescription();
    if ($description !== null) {
      ?>
      <p class="small text-muted">
          <?php echo $description; ?>
      </p>
      <?php
    }
    foreach ($section->getSubsections() as $subsection) {
      $this->printSubsection($subsection);
    }
  }

  /**
   * 
   * @return void
   */
  public function printSubsection(ControlPanelSubsection $subsection) {
    ?>
    <div class="row form-group">
        <label class="col-form-label col-sm-3">
            <?php echo $subsection->getLabel(); ?>
        </label>
        <div class="col-sm-9">
            <?php
            foreach ($subsection->getElements() as $element) {
              $this->printElement($element);
            }
            ?>
        </div>
    </div>
    <?php
  }

  public function printElement(ControlPanelElement $element) {
    if ($element instanceof ControlPanelCheckbox) {
      $this->printControlPanelCheckbox($element);
    } else if ($element instanceof ControlPanelFactRestriction) {
      $this->printControlPanelFactRestriction($element);
    } else if ($element instanceof ControlPanelRange) {
      $this->printControlPanelRange($element);
    } else if ($element instanceof ControlPanelRadioButtons) {
      $this->printControlPanelRadioButtons($element);
    } else {
      throw new Exception("unsupported ControlPanelElement");
    }

    $description = $element->getDescription();
    if ($description !== null) {
      ?>
      <p class="small text-muted">
          <?php echo $description; ?>
      </p>
      <?php
    }
  }

  public function printControlPanelCheckbox(ControlPanelCheckbox $element) {
    $value = $this->module->getPreference($element->getSettingKey(), $element->getSettingDefaultValue());

    //ugly positioning of checkbox - for now, build checkbox directly (as in admin_trees_config)
    /*
      ?>
      <div class="optionbox">
      <?php echo ViewUtils::checkbox($element->getSettingKey(), $value, $element->getLabel()); ?>
      </div>
      <?php
     */
    ?>
    <div class="form-check">
        <label for="<?= $element->getSettingKey() ?>">
            <input name="<?= $element->getSettingKey() ?>" type="checkbox" id="<?= $element->getSettingKey() ?>" value="<?= $element->getSettingKey() ?>" <?= $value ? 'checked' : '' ?>>
            <?= $element->getLabel() ?>
        </label>
    </div>
    <?php
  }

  public function printControlPanelFactRestriction(ControlPanelFactRestriction $element) {
    //why escape only here?	
    $value = e($this->module->getPreference($element->getSettingKey(), $element->getSettingDefaultValue()));
    ?>
    <div class="col-sm-9">
        <?= Bootstrap4::multiSelect(GedcomTag::getPicklistFacts($element->getFamily() ? 'FAM' : 'INDI'), explode(',', $value), ['id' => $element->getSettingKey(), 'name' => $element->getSettingKey() . '[]', 'class' => 'select2']) ?>
    </div>
    <?php
  }

  public function printControlPanelRange(ControlPanelRange $element) {
    $value = $this->module->getPreference($element->getSettingKey(), $element->getSettingDefaultValue());
    ?>
    <div class="input-group" style="min-width: 300px; max-width: 300px;">
        <label class="input-group-addon" for="<?php echo $element->getSettingKey(); ?>"><?php echo $element->getLabel() ?></label>
        <?php echo ViewUtils::select($element->getSettingKey(), array_combine(range($element->getMin(), $element->getMax()), range($element->getMin(), $element->getMax())), $value) ?>
    </div>
    <?php
  }

  public function printControlPanelRadioButtons(ControlPanelRadioButtons $element) {
    if ($element->getInline()) {
      $this->printControlPanelRadioButtonsInline($element);
      return;
    }

    $value = $this->module->getPreference($element->getSettingKey(), $element->getSettingDefaultValue());
    foreach ($element->getValues() as $radioButton) {
      ?>
      <label>
          <input type="radio" name="<?php echo $element->getSettingKey(); ?>" value="<?php echo $radioButton->getValue(); ?>" <?php echo ($value === $radioButton->getValue()) ? 'checked' : ''; ?>>
          <?php echo $radioButton->getLabel(); ?>
      </label>
      <br>
      <?php
      $description = $radioButton->getDescription();
      if ($description !== null) {
        ?>
        <p class="small text-muted">
            <?php echo $description; ?>
        </p>
        <?php
      }
    }
  }

  public function printControlPanelRadioButtonsInline(ControlPanelRadioButtons $element) {
    $options = array();
    foreach ($element->getValues() as $value) {
      $options[$value->getValue()] = $value->getLabel();
      //note: description, if any, not displayed in inline mode!
    }

    $value = $this->module->getPreference($element->getSettingKey(), $element->getSettingDefaultValue());

    echo Bootstrap4::radioButtons(
            $element->getSettingKey(),
            $options,
            $value,
            true);
  }

  /**
   * 
   * @return void
   */
  public function savePostData(Request $request, ControlPanelPreferences $prefs) {
    foreach ($prefs->getSections() as $section) {
      foreach ($section->getSubsections() as $subsection) {
        foreach ($subsection->getElements() as $element) {
          if ($element instanceof ControlPanelFactRestriction) {
            $this->module->setPreference($element->getSettingKey(), implode(',', $request->get($element->getSettingKey())));
          } else if ($element instanceof ControlPanelCheckbox) {
            $this->module->setPreference($element->getSettingKey(), ($request->get($element->getSettingKey()) !== null));
          } else {
            $this->module->setPreference($element->getSettingKey(), $request->get($element->getSettingKey()));
          }
        }
      }
    }

    FlashMessages::addMessage(I18N::translate('The preferences for the module “%s” have been updated.', $this->module->title()), 'success');
  }

}
