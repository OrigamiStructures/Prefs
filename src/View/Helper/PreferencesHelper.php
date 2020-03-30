<?php


namespace Prefs\View\Helper;

use Cake\View\Helper;
use App\Constants\PrefCon;


class PreferencesHelper extends Helper
{
    public $helpers = ['Form', 'Html'];

    protected $pageBreaks = [5, 10, 25, 50, 100];

    /**
     * @param $formContext
     */
    public function peoplePagination($formContext)
    {
        //place the form in a view block
        $this->getView()->append('prefs_form');

        echo $this->Form->create($formContext, [
            'url' => ['controller' => 'preferences', 'action' => 'setPrefs']
        ]);
        echo $this->Html->tag(
            'ul',
            $this->Form->control(PrefCon::PAGINATION_LIMIT)
            . $this->Form->control(
                PrefCon::PAGINATION_SORT_PEOPLE, [
                'options' => PrefCon::selectList(PrefCon::PAGINATION_SORT_PEOPLE),]),
            ['class' => 'menu']
        );
        echo $this->Form->control('id', ['type' => 'hidden']);
        echo $this->getView()->fetch('additional_controls');
        echo $this->Form->submit();
        echo $this->Form->end();

        //close the view block
        $this->getView()->end();
    }
}
