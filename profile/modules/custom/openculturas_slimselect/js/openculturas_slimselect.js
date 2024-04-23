(function openculturasSlimSelect(Drupal, once) {
  Drupal.behaviors.slimSelectBehaviour = {
    attach(context) {
      once('slimSelectBehaviour', 'select.slimselect', context).forEach(
        function slimSelectBehaviour(slimSelectElement) {
          // eslint-disable-next-line no-undef
          const slimSelect = new SlimSelect({
            select: slimSelectElement,
            settings: {
              allowDeselect:
                slimSelectElement.dataset.allowDeselect !== undefined
                  ? parseInt(slimSelectElement.dataset.allowDeselect, 10)
                  : 1,
              closeOnSelect:
                slimSelectElement.dataset.closeOnSelect !== undefined
                  ? parseInt(slimSelectElement.dataset.closeOnSelect, 10)
                  : 0,
              maxValuesMessage: `{number} ${Drupal.t('selected')}`,
              placeholderText: `${Drupal.t('Select value')}`,
              searchPlaceholder: `${Drupal.t('Search')}`,
              searchText: `${Drupal.t('No results')}`,
              searchingText: `${Drupal.t('Searching…')}`,
            },
          });

          if (
            slimSelectElement.dataset.selectAll !== undefined
              ? parseInt(slimSelectElement.dataset.selectAll, 10)
              : 0 === 1
          ) {
            const slimSelectData = [];
            slimSelect.getData().forEach((slimSelectDatapoint) => {
              if (slimSelectDatapoint.selectAll === false) {
                slimSelectDatapoint.selectAll = true;
                slimSelectDatapoint.selectAllText = Drupal.t('Select all');
              }
              slimSelectData.push(slimSelectDatapoint);
            });
            slimSelect.setData(slimSelectData);
          }
        },
      );
    },
  };
})(Drupal, once);
