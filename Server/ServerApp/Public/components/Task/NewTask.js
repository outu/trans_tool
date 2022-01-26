import {API_CREATE_NEW_TASK} from "../../public/api.js";

export default {
    name: 'NewTask',
    template: `
    <div class="build build-linux-client">
        <form>
        <div class="form-row">
            <div class="form-group col-md-3">
              <label for="inputVersion">FTP IP</label>
              <input type="text" class="form-control" id="inputVersion" placeholder=" " v-model="ip">
            </div>
           <div class="form-group col-md-3">
              <label for="inputVersion">FTP PORT</label>
              <input type="text" class="form-control" id="inputVersion" placeholder=" " v-model="port">
            </div>
            <div class="form-group col-md-3">
              <label for="inputVersion">FTP USER</label>
              <input type="text" class="form-control" id="inputVersion" placeholder=" " v-model="user">
            </div>
            <div class="form-group col-md-3">
              <label for="inputVersion">FTP PASSWORD</label>
              <input type="text" class="form-control" id="inputVersion" placeholder=" " v-model="password">
            </div>
            <div class="form-group col-md-4">
              <label for="inputVersion">TRANS FILE OR DIR</label>
              <input type="text" class="form-control" id="inputVersion" placeholder="Multiple files are separated by '|'" v-model="selectedFile">
            </div>
             
          </div>
        </form>
        <div>
            <button type="button" class="btn btn-primary" v-on:click="newTask">create</button>
        </div>
        <div v-if="!!buildButtonDisabled" class="spinner-border text-primary mt-2" role="status"></div>
          <!-- Modal -->
        <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalCenterTitle">created info</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                {{ message }}
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
    `,
    data(){
        return {
            message: '',
            ip: '127.0.0.1',
            port: '22',
            user: '',
            password: '',
            selectedFile: '',
            buildButtonDisabled: false,
        }
    },

    methods: {
        newTask: function () {
            this.buildButtonDisabled = true;
            API_CREATE_NEW_TASK({
                sIp: this.ip,
                sPort: this.port,
                sUser: this.user,
                sPassword: this.password,
                sSelectedFile: this.selectedFile,
            })
                .then((response) => {
                    console.log("THEN_1", response);
                    this.message = response.data.data + " task has been added this time";
                    $('#exampleModalCenter').modal();
                    this.buildButtonDisabled = false;
                })
                .catch((error) => {
                    console.log("CATCH_1", error);
                    this.message = error;
                    $('#exampleModalCenter').modal();
                    this.buildButtonDisabled = false;
                });
        },
    }

};