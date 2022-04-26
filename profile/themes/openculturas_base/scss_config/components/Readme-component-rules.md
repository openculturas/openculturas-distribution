# Module Rules

**"Module are designed to exist as a standalone component"**

In Drupal teminology we name them **Components**.
Same rules apply for the components in  `templates/**.scss`.

**Components must be wrapped**. It is not allowed to style plain html elements within components.

SMACSS says:

As briefly mentioned in the previous section, a Module is a more discrete component of the page. It is your navigation bars and your carousels and your dialogs and your widgets and so on. This is the meat of the page. Modules sit inside Layout components. Modules can sometimes sit within other Modules, too. Each Module should be designed to exist as a standalone component. In doing so, the page will be more flexible. If done right, Modules can easily be moved to different parts of the layout without breaking.

When defining the rule set for a module, avoid using IDs and element selectors, sticking only to class names. A module will likely contain a number of elements and there is likely to be a desire to use descendent or child selectors to target those elements.


Example Component/Module Styles:

```
.module > h2 {
    padding: 5px;
}

.module span {
    padding: 5px;
}
```

Please refer to http://smacss.com/book/type-module
