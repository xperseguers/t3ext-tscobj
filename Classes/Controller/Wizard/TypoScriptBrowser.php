<?php
declare(strict_types=1);

namespace Causal\Tscobj\Controller\Wizard;

use Causal\Tscobj\Http\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Wizard\AbstractWizardController;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TypoScriptBrowser extends AbstractWizardController
{
    /**
     * @var string
     */
    private $content;

    /**
     * Wizard parameters, coming from FormEngine linking to the wizard.
     *
     * @var array
     */
    private $P;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:tscobj/Resources/Private/Language/locallang_wizards.xlf');

        $this->init();
    }

    /**
     * Initialization of the class
     */
    protected function init(): void
    {
        $this->P = GeneralUtility::_GP('P');
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->processRequestV9($request);
    }

    /**
     * Process request function
     * Makes a header-location redirect to an edit form IF POSSIBLE from the passed data - otherwise the window will
     * just close.
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function processRequestV9(ServerRequestInterface $request): ResponseInterface
    {
        $this->main();

        return new HtmlResponse($this->content);
    }

    /**
     * Main function, rendering the wizard
     */
    protected function main(): void
    {
        $this->getButtons();
        $this->moduleTemplate->setTitle($this->getLanguageService()->getLL('title'));

        if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
            $this->setPagePath($this->P['table'], (int)$this->P['uid']);

            $this->content .= '<h2>' . htmlspecialchars($this->getLanguageService()->getLL('title')) . '</h2>'
                . '<div>' . $this->typoScriptWizard() . '</div>';
        }

        $this->moduleTemplate->setContent($this->content);

        $this->content = $this->moduleTemplate->renderContent();
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
            $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

            // Close
            $closeButton = $buttonBar->makeLinkButton()
                ->setHref($this->P['returnUrl'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL));
            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }
    }

    /**
     * Creates the correct path to the current record
     *
     * @param string $table
     * @param int $uid
     */
    protected function setPagePath(string $table, int $uid): void
    {
        if ($table === 'pages') {
            $pageId = $uid;
        } else {
            $record = BackendUtility::getRecord($table, $uid, '*', '', false);
            $pageId = $record['pid'];
        }

        $pageAccess = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(1));
        if (is_array($pageAccess)) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageAccess);
        }
    }

    /**
     * @return string
     */
    protected function typoScriptWizard(): string
    {
        $description = $this->getLanguageService()->getLL('wizard_description');

        return <<<HTML
<p>${description}</p>
<a href="https://www.paypal.me/simonschaufi" target="_blank" class="btn btn-primary">
    <strong><i class="fa fa-paypal" aria-hidden="true"></i> Donate now</strong>
</a>
- or -
<a href="https://github.com/sponsors/simonschaufi" target="_blank" class="btn btn-default">
    <strong><i class="fa fa-heart-o" style="color: #ea4aaa;" aria-hidden="true"></i> Sponsor via Github</strong>
</a>
HTML;
    }

    /**
     * Gets the current backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
