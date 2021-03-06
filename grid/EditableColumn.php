<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014
 * @package yii2-grid
 * @version 2.0.0
 */

namespace kartik\grid;

use Yii;
use Closure;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use kartik\editable\Editable;

/**
 * The EditableColumn converts the data to editable using
 * the Editable widget [[\kartik\editable\Editable]]
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class EditableColumn extends DataColumn
{
    
    /**
     * @var array|Closure the configuration options for the [[\kartik\editable\Editable]] widget. If not set as an array,
     * this can be passed as a callback function of the signature: `function ($model, $key, $index)`, where:
     * - $model mixed is the data model
     * - $key mixed is the key associated with the data model
     * - $index integer is the zero-based index of the data model among the models array returned by [[GridView::dataProvider]].
     */
    public $editableOptions = [];
    
    /**
     * @var boolean whether to refresh the grid on successful submission of editable
     */
    public $refreshGrid = false;
    
    /**
     * @var array the computed editable options
     */
    protected $_editableOptions = [];
    
    /**
     * Renders the data cell content.
     * @param mixed $model the data model
     * @param mixed $key the key associated with the data model
     * @param integer $index the zero-based index of the data model among the models array returned by [[GridView::dataProvider]].
     *
     * @return string the rendering result
     * @throws InvalidConfigException
     */
    public function renderDataCellContent($model, $key, $index)
    {
        $exception = false;
        $this->_editableOptions = $this->editableOptions;
        if (!empty($this->editableOptions) && $this->editableOptions instanceof Closure) {
            $this->_editableOptions = call_user_func($this->editableOptions, $model, $key, $index);
        }
        if (!is_array($this->_editableOptions)) {
            $this->_editableOptions = [];
        }
        if ($this->grid->pjax && empty($this->_editableOptions['pjaxContainerId'])) {
            $this->_editableOptions['pjaxContainerId'] = $this->grid->pjaxSettings['options']['id'];
        }
        if ($this->attribute !== null) {
            $this->_editableOptions['model'] = $model;
            $this->_editableOptions['attribute'] = '[' . $index . ']' . $this->attribute;
        } elseif (empty($this->_editableOptions['name']) && empty($this->_editableOptions['model'])) {
            $exception = true;
        } elseif (empty($this->_editableOptions['attribute'])) {
            $exception = true;
        }
        if ($exception) {
            throw new InvalidConfigException("You must setup either the 'attribute' for the EditableColumn OR setup the 'name' OR 'model'/'attribute' in '_editableOptions' (Exception raised for: key: '{$key}', index: '{$index}').");
        }
        $this->_editableOptions['displayValue'] = parent::renderDataCellContent($model, $key, $index);
        $params = Html::hiddenInput('editableIndex', $index) . Html::hiddenInput('editableKey', $key);
        if (empty($this->_editableOptions['beforeInput'])) {
            $this->_editableOptions['beforeInput'] = $params;
        } else {
            $this->_editableOptions['beforeInput'] .= $params;
        }
        if ($this->refreshGrid) {
            $view = $this->grid->getView();
            $grid = 'jQuery("#' . $this->grid->options['id'] . '")';
            $script =<<< JS
{$grid}.find('.kv-editable-input').each(function() {
    var \$input = $(this);
    \$input.on('editableSuccess', function(){
        {$grid}.yiiGridView("applyFilter");
    });
});
JS;
            $view->registerJs($script);
        }
        return Editable::widget($this->_editableOptions);
    }
}