<?php
	use \OCA\Files_External\Lib\Backend\Backend;
	use \OCA\Files_External\Lib\DefinitionParameter;
	use \OCA\Files_External\Service\BackendService;

	script('files_external', 'settings');
	style('files_external', 'settings');

	// load custom JS
	foreach ($_['backends'] as $backend) {
		if ($backend->getCustomJs()) {
			script('files_external', $backend->getCustomJs());
		}
	}
	foreach ($_['authMechanisms'] as $authMechanism) {
		if ($authMechanism->getCustomJs()) {
			script('files_external', $authMechanism->getCustomJs());
		}
	}

	function writeParameterInput($parameter, $options, $classes = []) {
		$value = '';
		if (isset($options[$parameter->getName()])) {
			$value = $options[$parameter->getName()];
		}
		$placeholder = $parameter->getText();
		$is_optional = $parameter->isFlagSet(DefinitionParameter::FLAG_OPTIONAL);

		switch ($parameter->getType()) {
		case DefinitionParameter::VALUE_PASSWORD: ?>
			<?php if ($is_optional) { $classes[] = 'optional'; } ?>
			<input type="password"
				<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				value="<?php p($value); ?>"
				placeholder="<?php p($placeholder); ?>"
			/>
			<?php
			break;
		case DefinitionParameter::VALUE_BOOLEAN: ?>
			<label>
				<input type="checkbox"
					<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
					data-parameter="<?php p($parameter->getName()); ?>"
				 	<?php if ($value === true): ?> checked="checked"<?php endif; ?>
				/>
				<?php p($placeholder); ?>
			</label>
			<?php
			break;
		case DefinitionParameter::VALUE_HIDDEN: ?>
			<input type="hidden"
				<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				value="<?php p($value); ?>"
			/>
			<?php
			break;
		default: ?>
			<?php if ($is_optional) { $classes[] = 'optional'; } ?>
			<input type="text"
				<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				value="<?php p($value); ?>"
				placeholder="<?php p($placeholder); ?>"
			/>
			<?php
		}
	}
?>
<form id="files_external" class="section" data-encryption-enabled="<?php echo $_['encryptionEnabled']?'true': 'false'; ?>">
	<h2><?php p($l->t('External Storage')); ?></h2>
	<?php if (isset($_['dependencies']) and ($_['dependencies']<>'')) print_unescaped(''.$_['dependencies'].''); ?>
	<table id="externalStorage" class="grid" data-admin='<?php print_unescaped(json_encode($_['permissionType'] === BackendService::USER_ADMIN)); ?>'>
		<thead>
			<tr>
				<th></th>
				<th><?php p($l->t('Folder name')); ?></th>
				<th><?php p($l->t('External storage')); ?></th>
				<th><?php p($l->t('Authentication')); ?></th>
				<th><?php p($l->t('Configuration')); ?></th>
				<?php if ($_['permissionType'] === BackendService::USER_ADMIN) print_unescaped('<th>'.$l->t('Available for').'</th>'); ?>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<tr id="addMountPoint">
				<td class="status">
					<span></span>
				</td>
				<td class="mountPoint"><input type="text" name="mountPoint" value=""
					placeholder="<?php p($l->t('Folder name')); ?>">
				</td>
				<td class="backend">
					<select id="selectBackend" class="selectBackend" data-configurations='<?php p(json_encode($_['backends'])); ?>'>
						<option value="" disabled selected
							style="display:none;">
							<?php p($l->t('Add storage')); ?>
						</option>
						<?php
							$sortedBackends = array_filter($_['backends'], function($backend) use ($_) {
								return $backend->isPermitted($_['permissionType'], BackendService::PERMISSION_CREATE);
							});
							uasort($sortedBackends, function($a, $b) {
								return strcasecmp($a->getText(), $b->getText());
							});
						?>
						<?php foreach ($sortedBackends as $backend): ?>
							<option value="<?php p($backend->getIdentifier()); ?>"><?php p($backend->getText()); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="authentication" data-mechanisms='<?php p(json_encode($_['authMechanisms'])); ?>'></td>
				<td class="configuration"></td>
				<?php if ($_['permissionType'] === BackendService::USER_ADMIN): ?>
					<td class="applicable" align="right">
						<input type="hidden" class="applicableUsers" style="width:20em;" value="" />
					</td>
				<?php endif; ?>
				<td class="mountOptionsToggle hidden">
					<img class="svg action"
						title="<?php p($l->t('Advanced settings')); ?>"
						alt="<?php p($l->t('Advanced settings')); ?>"
						src="<?php print_unescaped(image_path('core', 'actions/settings.svg')); ?>"
					/>
					<input type="hidden" class="mountOptions" value="" />
				</td>
				<td class="hidden">
					<img class="svg action"
						alt="<?php p($l->t('Delete')); ?>"
						title="<?php p($l->t('Delete')); ?>"
						src="<?php print_unescaped(image_path('core', 'actions/delete.svg')); ?>"
					/>
				</td>
			</tr>
		</tbody>
	</table>
	<br />

	<?php if ($_['permissionType'] === BackendService::USER_ADMIN): ?>
		<br />
		<input type="checkbox" name="allowUserMounting" id="allowUserMounting"
			value="1" <?php if ($_['allowUserMounting'] == 'yes') print_unescaped(' checked="checked"'); ?> />
		<label for="allowUserMounting"><?php p($l->t('Enable User External Storage')); ?></label> <span id="userMountingMsg" class="msg"></span>

		<p id="userMountingBackends"<?php if ($_['allowUserMounting'] != 'yes'): ?> class="hidden"<?php endif; ?>>
			<?php p($l->t('Allow users to mount the following external storage')); ?><br />
			<?php
				$userBackends = array_filter($_['backends'], function($backend) {
					return $backend->isAllowedPermitted(BackendService::USER_PERSONAL, BackendService::PERMISSION_MOUNT);
				});
			?>
			<?php $i = 0; foreach ($userBackends as $backend): ?>
				<input type="checkbox" id="allowUserMountingBackends<?php p($i); ?>" name="allowUserMountingBackends[]" value="<?php p($backend->getIdentifier()); ?>" <?php if ($backend->isPermitted(BackendService::USER_PERSONAL, BackendService::PERMISSION_MOUNT)) print_unescaped(' checked="checked"'); ?> />
				<label for="allowUserMountingBackends<?php p($i); ?>"><?php p($backend->getText()); ?></label> <br />
				<?php $i++; ?>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>
</form>
