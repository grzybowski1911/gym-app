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
        jsonLiftData.forEach(element => {
            //console.log(element);
            let regex = /[0-9]{4}-[0-9]{2}-[0-9]{2}/i;
            liftDates.push(element.date.match(regex)[0]);
            liftNames.push(element.lift_name);
            //console.log( Number(element.weight) * Number(element.reps) * Number(element.sets) );
            totalWeight.push(Number(element.weight) * Number(element.reps) * Number(element.sets));
        });
        //console.log(totalWeight);

        const data = {
            labels: liftNames,
            datasets: [{
              label: 'Weight Moved',
              backgroundColor: 'rgb(255, 99, 132)',
              borderColor: 'rgb(255, 99, 132)',
              data: totalWeight,
            }]
          };
        
          const config = {
            type: 'line',
            data: data,
            options: {}
          };
        
          const myChart = new Chart(
            document.getElementById('myChart'),
            config
          );
    };
    // Send request for data to "api" route
    dateReq.open("get", "api/lift-data", true);
    dateReq.send();
}


