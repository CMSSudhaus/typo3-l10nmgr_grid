# l10nmgr_grid
The Extension expands the Basic [l10nmgr Plugin](http://typo3.org/extensions/repository/view/l10nmgr). Now you can define as many Gridelement's individual FlexForms as you need and Translate all without Problems.

## Why we expand
Each Grid-System was no Problem because the Default Plugin only Exports the normal **tt_content** Fields from an Element.
But we defined for our Customers Individual FlexForms, for Slider, Flip Objects, Modals and many more.
Also there was missing something and we need to do this Extension.

## Introduction
You can Export all Fields from your Gridelements and import them as an **Translation**. You Can't import the translation as the Default Language, we haven't do that yet. If you need that Feature please give us Feedback.

### Configuration
If you have Installed the Plugin you, can set some config's in the Extension Manager. The Default Values are **empty**.
In the Configuration the **Exclude** is always leading

#### Include
In this Field you can that an Limited Export of Gridelements. For Example if you have 5 Gridelements and only 1 of them has FlexForm fields to Translate. Add the Name of that FlexForm and only this one will be added to the Export.

#### Exclude
The fields exlude defined FlexForm's from the export.
For Example if you have 5 Gridelements and only 1 of them has **no** FlexForm fields to Translate. Add the Name of that FlexForm and only this one will no be in to the Export.

#### FlexForm-Field Include
This field can define which field of the FlexForm ist added to the Export. So you can decide which field need to be Translated.

For Example:<br/>
``Flip:Headline``<br/>
this means that only the **Headline**-Field of the **Flip**-FlexForm will be exported.

You also can place an ``*`` as a wildcard to the config:<br/>
`*:Headline`<br/>
Now will be all **Headlines** from **all** FlexForms exported.

#### FlexForm-Field Exclude
This field can be used to exclude Specific FlexForm-Fields from the Export.

For Example:<br/>
``Flip:direction``<br/>
Now the **Direction** field of the **Flip**-FlexForm will not be exported.

You also can place an ``*`` as a wildcard to the config:<br/>
`*:css`<br/>
Now will be all **css**-Fields not be exported.

#### Wildcard Pattern
You can't use an ``*`` as Placeholder, it is only defined as Wildcard Syntax.

## Please give us feedback
Now we want to share our work with you.
We will be proud if you give us feedback or ideas what we can do better or what is missing.

### Contact us
- [Homepage](http://web-kon.de)
- [Git-Hub](https://bitbucket.org/gutenberghaus/l10nmgr_ext)
- [E-Mail](mailto:technik@web-kon.de)
