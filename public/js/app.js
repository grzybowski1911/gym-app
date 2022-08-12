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

  var oReq = new XMLHttpRequest(); // Create a new request object
  oReq.onload = function() {

    let liftData = (this.responseText);

    liftData.forEach()

    console.log(liftData);
    const labels = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
      ];
    
      const data = {
        labels: labels,
        datasets: [{
          label: 'Weight Moved',
          backgroundColor: 'rgb(255, 99, 132)',
          borderColor: 'rgb(255, 99, 132)',
          data: [0, 10, 5, 2, 20, 30, 45],
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
  oReq.open("get", "api", true);
  oReq.send();

