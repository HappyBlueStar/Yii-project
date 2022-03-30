<?php
/**
 * @var $this yii\web\View
 * @var $versions array all available API versions
 * @var $version string the currently chosen API version
 * @var $section string the currently active API file
 */
use app\widgets\DropdownList;
use app\models\Guide;
use yii\helpers\Html;

?>
<nav class="version-selector" role="navigation">
    <div class="btn-group btn-group-justified btn-group-2-element">
	    <?php
            $items = [];
	        $guide = Guide::load($version, 'en');
	        if (!isset($extension) && $guide) {
                if ($guide->getDownloadFile('tar.gz') !== false) {
                    $items[] = [
                        'label' => 'Offline HTML (tar.gz)',
                        'url' => ['guide/download', 'version' => $guide->version, 'language' => $guide->language, 'format' => 'tar.gz'],
                    ];
                }
                if ($guide->getDownloadFile('tar.bz2') !== false) {
                    $items[] = [
                        'label' => 'Offline HTML (tar.bz2)',
                        'url' => ['guide/download', 'version' => $guide->version, 'language' => $guide->language, 'format' => 'tar.bz2'],
                    ];
                }
            }
			if (!empty($items)) {
				echo DropdownList::widget([
					'tag' => 'div',
					'selection' => 'Download',
					'items' => $items,
					'options' => [
						'class' => 'btn-group btn-group-sm'
					]
				]);
			}
		?>

        <?php
        $versionItems = [];
        foreach ($versions as $ver) {
            if ($ver === $version) {
                continue;
            }

            if (isset($extension)) {
                list($extensionVendor, $extensionName) = explode('/', $extension->name, 2);

                // do not provide link to version where class does not exist
                $sectionFile = Yii::getAlias("@app/data/extensions/{$extension->name}/api-$ver/$section.html");
                if (!is_file($sectionFile)) {
                    continue;
                }

                $versionItems[] = [
                    'label' => $ver,
                    'url' => $section === 'index' ?
                        ['api/extension-index', 'version' => $ver, 'vendorName' => $extensionVendor, 'name' => $extensionName] :
                        ['api/extension-view', 'version' => $ver, 'vendorName' => $extensionVendor, 'name' => $extensionName, 'section' => $section],
                ];
            } else {
                $versionItems[] = [
                    'label' => $ver,
                    'url' => ($version[0] !== $ver[0] || $section === 'index') ?
                        ['api/index', 'version' => $ver] :
                        ['api/view', 'version' => $ver, 'section' => $section],
                ];
            }
        }
        ?>
        <?= DropdownList::widget([
            'tag' => 'div',
            'selection' => "Version {$version}",
            'items' => $versionItems,
            'options' => [
                'class' => 'btn-group btn-group-sm'
            ],
        ]) ?>
    </div>
</nav>
