# l10nmgr_grid
The extension expands the Basic [l10nmgr Plugin](http://typo3.org/extensions/repository/view/l10nmgr). Now you can define as many gridelements separated FlexForms as you need and translate them all without any problems.

## Why we expand
Each Grid-System was not a Problem because the default plugin only exports the regular **tt_content** fields from an element.
But we have defined for our customers individual FlexForms, for example for Slider, flipping objects, modals and many more, which were not translated.
Also parts were missing, so we decided to expand the extension.

## Introduction
You can export all fields from your gridelements and import them as an **Translation**. You can't import the translation as the default language, we haven't don that yet. But if you need that feature simply give us feedback and we will implement it soon.

### Configuration
If you have installed the plugin you, can set some config's in the extension manager. The dfault values are **empty**.
In the configuration the **Exclude** is always leading.

#### Include
In this field you can do an limited export of gridelements. For example if you have 5 gridelements and only one of them has FlexForm fields to translate. Add the name of that FlexForm and only this one will be added to the export.

#### Exclude
The fields which are defined as exkluded will not be considered in the FlexForms for the export.
For example if you have five gridelements and only one of them has **no** FlexForm fields to translate. Add the name of that FlexForm field and only this one will no be considered for the Export.

#### FlexForm-Field Include
This field can define which field of the FlexForm ist added to the export. So you can decide which field needs to be translated.

For Example:<br/>
``Flip:Headline``<br/>
this means that only the **Headline**-Field of the **Flip**-FlexForm will be exported.

You also can place an ``*`` as a wildcard to the config:<br/>
`*:Headline`<br/>
Now there will be all **Headlines** from **all** FlexForms exported.

#### FlexForm-Field Exclude
This field can be used to exclude specific FlexForm-Fields from the export.

For Example:<br/>
``Flip:direction``<br/>
Now the **Direction** field of the **Flip**-FlexForm will not be exported.

You also can place an ``*`` as a wildcard to the config:<br/>
`*:css`<br/>
Now will be all **css**-fields not be exported.

#### Wildcard Pattern
You can't use an ``*`` as placeholder, it is only defined as Wildcard syntax.

## Please give us feedback
Now we want to share our work with you.
We would appreciate any kind of feedback or ideas for further developments to keep improving the extension for your needs.

### Contact us
- [E-Mail](mailto:technik@web-kon.de)
- [GitHub](https://github.com/CMSSudhaus/l10nmgr_grid)
- [Homepage](http://web-kon.de/leistungen/typo3-extension-l10nmgr-grid/)
- [TYPO3.org](http://typo3.org/extensions/repository/view/l10nmgr_grid)
