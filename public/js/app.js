/**
 * Add jQuery Validation plugin method for a valid password
 * 
 * Valid passwords contain at least one letter and one number.
 */
$.validator.addMethod('validPassword',
    function(value, element, param) {

        if (value != '') {
            if (value.match(/.*[a-z]+.*/i) == null) {
                return false;
            }
            if (value.match(/.*\d+.*/) == null) {
                return false;
            }
        }

        return true;
    },
    'Must contain at least one letter and one number'
);

if(document.getElementById('myChart')) {

    var dateReq = new XMLHttpRequest(); // Create a new request object
    dateReq.onload = function() {

        let liftData = (this.responseText);
        let jsonLiftData = JSON.parse(liftData);
        let liftNames = [];
        let liftDates = [];
        let totalWeight = [];
        let allLiftCats = [];
        jsonLiftData.forEach(element => {
            //console.log(element);
            let regex = /[0-9]{4}-[0-9]{2}-[0-9]{2}/i;
            liftDates.push(element.date.match(regex)[0]);
            liftNames.push(element.lift_name);
            //console.log( Number(element.weight) * Number(element.reps) * Number(element.sets) );
            totalWeight.push(Number(element.weight) * Number(element.reps) * Number(element.sets));
            allLiftCats.push(element.category);
        });

        // use new set to remove duplicates in an array & turn back into array after - not using atm 
        // let singleCats = [...new Set(liftCat)];

        let categories = ['chest', 'arms', 'back', 'core', 'legs', ''];
        let iter = 0; 
        let count = 0;
        let chest = 0;
        let arms = 0;
        let back = 0;
        let core = 0;
        let legs = 0; 
        let uncategorized = 0;
        for(cat of allLiftCats) {
            //if(cat == categories[iter]) {
            //    count++;
            //}
            if(cat == 'chest') {
                chest++;
            }
            if(cat == 'arms') {
                arms++;
            }
            if(cat == 'back') {
                back++;
            }
            if(cat == 'core') {
                core++;
            }
            if(cat == 'legs') {
                legs++;
            }
            if(cat == '') {
                uncategorized++;
            }
        }
        let totals = [];
        totals.push(chest);
        totals.push(arms);
        totals.push(back);
        totals.push(core);
        totals.push(legs);
        totals.push(uncategorized);

        console.log(totals);

        const data = {
            labels: categories,
            datasets: [{
              label: 'Lift Category',
              data: totals,
              backgroundColor: [
                '#F2EBDF',
                '#BFAC95',
                '#59281D',
                '#400909',
                '#A64B4B'
              ],
              hoverOffset: 4
            }]
          };

          const config = {
            type: 'doughnut',
            data: data,
        };

        const myChart = new Chart(
          document.getElementById('myChart'),
          config
        );

        //const data = {
        //    labels: categoriess,
        //    datasets: [{
        //      label: 'Weight Moved',
        //      backgroundColor: 'rgb(255, 99, 132)',
        //      borderColor: 'rgb(255, 99, 132)',
        //      data: totalWeight,
        //    }]
        //};
        
          //const config = {
          //  type: 'line',
          //  data: data,
          //  options: {}
          //};
        
          //const myChart = new Chart(
          //  document.getElementById('myChart'),
          //  config
          //);
    };
    // Send request for data to "api" route
    dateReq.open("get", "api/lift-data", true);
    dateReq.send();
}


