<?php

namespace AdminBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class LinkType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        unset($form);
        $view->vars['text'] = $options['attr']['text'];
        $view->vars['href'] = $options['attr']['href'];
        $view->vars['ajax'] = $options['attr']['ajax'] ?? false;
    }
}
