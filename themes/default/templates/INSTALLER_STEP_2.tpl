<form title="{!TERMS}" class="installer_cms_licence" action="install.php" method="post" autocomplete="off">
	<div class="installer_terms_title"><label for="licence">{!TERMS}</label></div>
	<div class="constrain_field">
		<textarea readonly="readonly" class="mono_textbox wide_field" id="licence" name="licence" cols="90" rows="17">{LICENCE*}</textarea>
	</div>
</form>

<form title="{!PRIMARY_PAGE_FORM}" action="{URL*}" method="post" autocomplete="off">
	{HIDDEN}

	<p class="proceed_button">
		<input class="button_screen buttons__yes" type="submit" value="{!I_AGREE}" />
	</p>
</form>
